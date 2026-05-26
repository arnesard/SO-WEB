<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikTagStockController extends Controller
{
    /**
     * Memuat filter drop-down awal (Daftar Warehouse Unik)
     */
    public function initFilters()
    {
        try {
            $warehouses = DB::table('so_all_wh_barcode_monstock_auto_db')
                ->whereNotNull('warehouse')
                ->where('warehouse', '!=', '')
                ->distinct()
                ->orderBy('warehouse', 'asc')
                ->pluck('warehouse');

            return response()->json([
                'status' => 'success',
                'warehouses' => $warehouses
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Ambil daftar operator stock dengan menghilangkan duplikasi nama & no_penneng
     */
    public function getOperatorsByWarehouse(Request $request)
    {
        $warehouse = $request->query('warehouse');

        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Warehouse wajib dipilih!'], 400);
        }

        try {
            // Menggunakan GROUP_CONCAT untuk menggabungkan lot jika operator punya lebih dari 1 record
            $operators = DB::table('so_all_wh_pic_stock_db')
                ->where('warehouse', $warehouse)
                ->whereNotNull('no_penneng')
                ->where('no_penneng', '!=', '')
                ->select(
                    'no_penneng',
                    'nama',
                    'gedung',
                    DB::raw("GROUP_CONCAT(lot ORDER BY lot ASC SEPARATOR ', ') as combined_lot")
                )
                ->groupBy('no_penneng', 'nama', 'gedung')
                ->orderBy('nama', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'operators' => $operators
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 SINKRONISASI CORES: Memproses baris berdasarkan banyak lot
     */
    public function processRows(Request $request)
    {
        $warehouse  = $request->warehouse;
        $operatorId = $request->operator_id; // Ingat, ini sekarang berisi no_penneng
        $docStart   = $request->doc_start;
        $docEnd     = $request->doc_end;

        if (empty($warehouse) || empty($operatorId)) {
            return response()->json(['status' => 'error', 'message' => 'Filter belum lengkap'], 400);
        }

        try {
            // 1. Ambil SEMUA baris PIC yang memiliki no_penneng tersebut
            $picInfos = DB::table('so_all_wh_pic_stock_db')
                ->where('no_penneng', $operatorId)
                ->where('warehouse', $warehouse)
                ->get();

            if ($picInfos->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);
            }

            // 2. Siapkan array filter lot
            $lotFilters = [];
            foreach ($picInfos as $pic) {
                $gedung = strtoupper(trim($pic->gedung));
                $lotRaw = trim($pic->lot);
                $lotParts = explode('-', $lotRaw);
                $lotFilters[] = [
                    'gedung' => $gedung,
                    'awal'   => trim($lotParts[0]),
                    'akhir'  => trim($lotParts[1] ?? $lotParts[0])
                ];
            }

            // 3. Query utama
            $query = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')
                        ->on('a.warehouse', '=', 'm.warehouse');
                });

            // 4. Pastikan data tidak bocor keluar dari warehouse yang dipilih
            $query->where('a.warehouse', $warehouse)
                ->where(function ($q) use ($lotFilters) {
                    foreach ($lotFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                                // FIX UTAMA: Tidak pakai CAST, pakai komparasi string langsung untuk lot seperti G01 - G31
                                ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [
                                    $filter['awal'],
                                    $filter['akhir']
                                ]);
                        });
                    }
                });

            // 5. Filter DOC
            if ($docStart && $docEnd) {
                $query->whereBetween('a.no_doc', [$docStart, $docEnd]);
            }

            $rows = $query
                ->select(
                    'a.loccode as lot_display',
                    'a.no_doc',
                    'a.item',
                    'm.description',
                    'a.Rak',
                    'a.Qty'
                )
                ->orderBy('a.no_doc', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'master_data' => $rows
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getDocuments(Request $request)
    {
        $operatorId = $request->query('operator_id'); // no_penneng
        $warehouse = $request->query('warehouse');

        // Ambil SEMUA baris karena 1 operator bisa punya beberapa gedung/lot
        $picInfos = DB::table('so_all_wh_pic_stock_db')
            ->where('no_penneng', $operatorId)
            ->when($warehouse, function ($q) use ($warehouse) {
                return $q->where('warehouse', $warehouse);
            })
            ->get();

        if ($picInfos->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan']);
        }

        $gedungList = $picInfos->pluck('gedung')->map(function ($g) {
            return strtoupper(trim($g)) . '-%';
        })->toArray();

        $docs = DB::table('so_all_wh_barcode_monstock_auto_db')
            ->where('warehouse', $picInfos->first()->warehouse)
            ->where(function ($q) use ($gedungList) {
                foreach ($gedungList as $gedungLike) {
                    $q->orWhere('loccode', 'LIKE', $gedungLike);
                }
            })
            ->whereNotNull('no_doc')
            ->distinct()
            ->orderBy('no_doc')
            ->pluck('no_doc');

        return response()->json([
            'status' => 'success',
            'documents' => $docs
        ]);
    }

    public function printTagStock(Request $request)
    {
        $warehouse   = $request->warehouse;
        $operatorId  = $request->operator_id; // no_penneng
        $docStart    = $request->doc_start;
        $docEnd      = $request->doc_end;

        // Ambil semua data PIC berdasarkan no_penneng
        $picInfos = DB::table('so_all_wh_pic_stock_db')
            ->where('no_penneng', $operatorId)
            ->where('warehouse', $warehouse)
            ->get();

        if ($picInfos->isEmpty()) {
            return back()->with('error', 'PIC tidak ditemukan');
        }

        $lotFilters = [];
        foreach ($picInfos as $pic) {
            $gedung = strtoupper(trim($pic->gedung));
            $lotParts = explode('-', trim($pic->lot));
            $lotFilters[] = [
                'gedung' => $gedung,
                'awal'   => trim($lotParts[0]),
                'akhir'  => trim($lotParts[1] ?? $lotParts[0])
            ];
        }

        // Query utama
        $rows = DB::table('so_all_wh_barcode_monstock_auto_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')
                    ->on('a.warehouse', '=', 'm.warehouse');
            })
            ->where('a.warehouse', $warehouse)
            ->where(function ($q) use ($lotFilters) {
                foreach ($lotFilters as $filter) {
                    $q->orWhere(function ($sub) use ($filter) {
                        $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                            ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [
                                $filter['awal'],
                                $filter['akhir']
                            ]);
                    });
                }
            })
            ->when($docStart && $docEnd, function ($q) use ($docStart, $docEnd) {
                $q->whereBetween('a.no_doc', [$docStart, $docEnd]);
            })
            ->select(
                'a.no_doc',
                'a.item',
                'a.loccode',
                'a.Rak',
                'a.Qty',
                'm.description as description_master'
            )
            ->orderBy('a.no_doc')
            ->get();

        // Mapping Data
        $data = $rows->map(function ($t) {
            $isOe = str_ends_with($t->item, '0');
            return [
                'noDoc'       => $t->no_doc,
                'item'        => $t->item,
                'description' => $t->description_master ?? '-',
                'rackcode'    => $t->Rak ?? 0,
                'oe'          => $isOe ? ($t->Qty ?? 0) : 0,
                'ok'          => !$isOe ? ($t->Qty ?? 0) : 0,
                'loccode'     => $t->loccode ?? '-',
            ];
        });

        // Kumpulkan data display untuk print headernya
        $picNameDisplay = $picInfos->first()->nama;
        $gedungDisplay = $picInfos->pluck('gedung')->unique()->implode(', ');
        $lotDisplay = $picInfos->pluck('lot')->implode(', ');

        return view('dashboard.oracle_vs_fisik.tag_stock_rev', [
            'rows'   => $data,
            'pic'    => $picNameDisplay,
            'gedung' => $gedungDisplay,
            'lot'    => $lotDisplay,
        ]);
    }

    /**
     * 🎯 SINKRONISASI CORES: Tombol Validasi (Bandingkan QTY dengan APPKSO per Operator)
     */
    public function validateAppkso(Request $request)
    {
        $warehouse  = $request->warehouse;
        $operatorId = $request->operator_id;
        $docStart   = $request->doc_start;
        $docEnd     = $request->doc_end;

        if (empty($warehouse) || empty($operatorId)) {
            return response()->json(['status' => 'error', 'message' => 'Filter belum lengkap'], 400);
        }

        try {
            $picInfos = DB::table('so_all_wh_pic_stock_db')
                ->where('no_penneng', $operatorId)
                ->where('warehouse', $warehouse)
                ->get();

            if ($picInfos->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);
            }

            $lotFilters = [];
            foreach ($picInfos as $pic) {
                $gedung = strtoupper(trim($pic->gedung));
                $lotParts = explode('-', trim($pic->lot));
                $lotFilters[] = [
                    'gedung' => $gedung,
                    'awal'   => trim($lotParts[0]),
                    'akhir'  => trim($lotParts[1] ?? $lotParts[0])
                ];
            }

            // Subquery untuk menjumlahkan Qty APPKSO per Dokumen & Item
            $subAppkso = DB::table('so_all_wh_appkso_db')
                ->select('nokso', 'item', DB::raw('SUM(qty) as total_qty'))
                ->groupBy('nokso', 'item');

            $query = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')
                        ->on('a.warehouse', '=', 'm.warehouse');
                })
                ->leftJoinSub($subAppkso, 'kso', function ($join) {
                    $join->on('a.no_doc', '=', 'kso.nokso')
                        ->on('a.item', '=', 'kso.item');
                })
                ->where('a.warehouse', $warehouse)
                ->where(function ($q) use ($lotFilters) {
                    foreach ($lotFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                                ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [
                                    $filter['awal'],
                                    $filter['akhir']
                                ]);
                        });
                    }
                });

            if ($docStart && $docEnd) {
                $query->whereBetween('a.no_doc', [$docStart, $docEnd]);
            }

            $rows = $query
                ->select(
                    'a.loccode as lot_display',
                    'a.no_doc',
                    'a.item',
                    'm.description',
                    'a.Rak',
                    'a.Qty as qty_tag',
                    DB::raw('COALESCE(kso.total_qty, 0) as qty_appkso')
                )
                ->orderBy('a.no_doc', 'asc')
                ->get();

            return response()->json(['status' => 'success', 'master_data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 SINKRONISASI CORES: Tombol Cek Doc (Seluruh Gudang Tanpa Filter Operator + Filter Selisih 0 + PIC)
     */
    public function checkDoc(Request $request)
    {
        $warehouse = $request->warehouse;

        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Gudang wajib dipilih'], 400);
        }

        try {
            // 1. Subquery APPKSO
            $subAppkso = DB::table('so_all_wh_appkso_db')
                ->select('nokso', 'item', DB::raw('SUM(qty) as total_qty'))
                ->groupBy('nokso', 'item');

            // 2. Ambil List PIC untuk Gudang ini
            $pics = DB::table('so_all_wh_pic_stock_db')
                ->where('warehouse', $warehouse)
                ->get();

            $picList = [];
            foreach ($pics as $p) {
                $parts = explode('-', trim($p->lot));
                $picList[] = [
                    'nama'   => $p->nama,
                    'gedung' => strtoupper(trim($p->gedung)),
                    'awal'   => trim($parts[0]),
                    'akhir'  => trim($parts[1] ?? $parts[0])
                ];
            }

            // 3. Query Utama Barcode vs KSO
            $rows = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')
                        ->on('a.warehouse', '=', 'm.warehouse');
                })
                ->leftJoinSub($subAppkso, 'kso', function ($join) {
                    $join->on('a.no_doc', '=', 'kso.nokso')
                        ->on('a.item', '=', 'kso.item');
                })
                ->where('a.warehouse', $warehouse)
                ->select(
                    'a.loccode as lot_display',
                    'a.no_doc',
                    'a.item',
                    'm.description',
                    'a.Qty as qty_tag',
                    DB::raw('COALESCE(kso.total_qty, 0) as qty_appkso')
                )
                ->orderBy('a.no_doc', 'asc')
                ->get();

            // 4. Mapping Filter Selisih & Penentuan Nama PIC
            $filteredRows = [];
            foreach ($rows as $r) {
                $selisih = $r->qty_tag - $r->qty_appkso;

                // 1.1 Exclude jika selisih = 0
                if ($selisih == 0) {
                    continue;
                }

                // 1.2 Cari Nama PIC berdasarkan Loccode (Gedung & Range Lot)
                $picName = '-';
                if (!empty($r->lot_display) && strpos($r->lot_display, '-') !== false) {
                    $lastDash = strrpos($r->lot_display, '-');
                    if ($lastDash !== false) {
                        $locGedung = strtoupper(substr($r->lot_display, 0, $lastDash));
                        $locLot = substr($r->lot_display, $lastDash + 1);

                        foreach ($picList as $pl) {
                            if ($pl['gedung'] === $locGedung && $locLot >= $pl['awal'] && $locLot <= $pl['akhir']) {
                                $picName = $pl['nama'];
                                break;
                            }
                        }
                    }
                }

                $r->pic_name = $picName;
                $r->selisih = $selisih;
                $filteredRows[] = $r;
            }

            return response()->json(['status' => 'success', 'master_data' => array_values($filteredRows)]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 SINKRONISASI CORES: Ambil Detail Histori APPKSO saat Baris Tabel Diklik
     */
    public function getScanHistory(Request $request)
    {
        $warehouse = $request->warehouse;
        $doc = $request->doc;
        $item = $request->item;

        try {
            $history = DB::table('so_all_wh_appkso_db')
                ->where('warehouse', $warehouse)
                ->where('nokso', $doc)
                ->where('item', $item)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
