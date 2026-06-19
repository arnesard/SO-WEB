<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagStockController extends Controller
{
    public function index()
    {
        return view('appkso.tag_stock');
    }

    public function initFilters()
    {
        try {
            $warehouses = DB::table('so_all_wh_barcode_monstock_auto_db')
                ->whereNotNull('warehouse')
                ->where('warehouse', '!=', '')
                ->select('warehouse', DB::raw('MAX(updated_at) as last_upload'))
                ->groupBy('warehouse')
                ->orderBy('warehouse', 'asc')
                ->get();

            $formattedWarehouses = $warehouses->map(function ($item) {
                $lastUpload = $item->last_upload
                    ? \Carbon\Carbon::parse($item->last_upload)->format('d/m/Y H:i:s')
                    : '-';

                return [
                    'warehouse' => $item->warehouse,
                    'last_upload' => $lastUpload
                ];
            });

            return response()->json(['status' => 'success', 'warehouses' => $formattedWarehouses]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getOperatorsByWarehouse(Request $request)
    {
        $warehouse = $request->query('warehouse');
        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Warehouse wajib dipilih!'], 400);
        }

        try {
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

            return response()->json(['status' => 'success', 'operators' => $operators]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function processRows(Request $request)
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

            if ($picInfos->isEmpty()) return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);

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

            $query = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')->on('a.warehouse', '=', 'm.warehouse');
                });

            $query->where('a.warehouse', $warehouse)
                ->where(function ($q) use ($lotFilters) {
                    foreach ($lotFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                                ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [$filter['awal'], $filter['akhir']]);
                        });
                    }
                });

            if ($docStart && $docEnd) {
                $query->whereBetween('a.no_doc', [$docStart, $docEnd]);
            }

            $rows = $query->select('a.loccode as lot_display', 'a.no_doc', 'a.item', 'm.description', 'a.Rak', 'a.Qty')
                ->orderBy('a.no_doc', 'asc')->get();

            return response()->json(['status' => 'success', 'master_data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getDocuments(Request $request)
    {
        $operatorId = $request->query('operator_id');
        $warehouse = $request->query('warehouse');

        $picInfos = DB::table('so_all_wh_pic_stock_db')
            ->where('no_penneng', $operatorId)
            ->when($warehouse, function ($q) use ($warehouse) {
                return $q->where('warehouse', $warehouse);
            })->get();

        if ($picInfos->isEmpty()) return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan']);

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
            ->whereNotNull('no_doc')->distinct()->orderBy('no_doc')->pluck('no_doc');

        return response()->json(['status' => 'success', 'documents' => $docs]);
    }

    public function printTagStock(Request $request)
    {
        $warehouse   = $request->warehouse;
        $operatorId  = $request->operator_id;
        $docStart    = $request->doc_start;
        $docEnd      = $request->doc_end;

        $picInfos = DB::table('so_all_wh_pic_stock_db')->where('no_penneng', $operatorId)->where('warehouse', $warehouse)->get();
        if ($picInfos->isEmpty()) return back()->with('error', 'PIC tidak ditemukan');

        $lotFilters = [];
        foreach ($picInfos as $pic) {
            $gedung = strtoupper(trim($pic->gedung));
            $lotParts = explode('-', trim($pic->lot));
            $lotFilters[] = ['gedung' => $gedung, 'awal' => trim($lotParts[0]), 'akhir' => trim($lotParts[1] ?? $lotParts[0])];
        }

        $rows = DB::table('so_all_wh_barcode_monstock_auto_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')->on('a.warehouse', '=', 'm.warehouse');
            })
            ->where('a.warehouse', $warehouse)
            ->where(function ($q) use ($lotFilters) {
                foreach ($lotFilters as $filter) {
                    $q->orWhere(function ($sub) use ($filter) {
                        $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                            ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [$filter['awal'], $filter['akhir']]);
                    });
                }
            })
            ->when($docStart && $docEnd, function ($q) use ($docStart, $docEnd) {
                $q->whereBetween('a.no_doc', [$docStart, $docEnd]);
            })
            ->select('a.no_doc', 'a.item', 'a.loccode', 'a.Rak', 'a.Qty', 'm.description as description_master')
            ->orderBy('a.no_doc')->get();

        $data = $rows->map(function ($t) {
            $isOe = str_ends_with($t->item, '0');
            return [
                'noDoc' => $t->no_doc,
                'item' => $t->item,
                'description' => $t->description_master ?? '-',
                'rackcode' => $t->Rak ?? 0,
                'oe' => $isOe ? ($t->Qty ?? 0) : 0,
                'ok' => !$isOe ? ($t->Qty ?? 0) : 0,
                'loccode' => $t->loccode ?? '-',
            ];
        });

        $picNameDisplay = $picInfos->first()->nama;
        $gedungDisplay = $picInfos->pluck('gedung')->unique()->implode(', ');
        $lotDisplay = $picInfos->pluck('lot')->implode(', ');

        return view('dashboard.oracle_vs_fisik.tag_stock_rev', [
            'rows' => $data,
            'pic' => $picNameDisplay,
            'gedung' => $gedungDisplay,
            'lot' => $lotDisplay,
        ]);
    }

    /**
     * ⚡ NEW: Ambil list SO Name dari koneksi kedua (fginvc) - Diurutkan Terbaru
     */
    public function getSoNamesCntso()
    {
        try {
            // Urutkan murni berdasarkan recid tertinggi (paling baru)
            $soNames = DB::connection('fginvc')->table('cntso')
                ->select('so_name', DB::raw('MAX(recid) as max_recid'))
                ->whereNotNull('so_name')
                ->where('so_name', '!=', '')
                ->groupBy('so_name')
                ->orderBy('max_recid', 'desc') // ⚡ PASTI TERBARU DI ATAS
                ->pluck('so_name');

            return response()->json(['status' => 'success', 'data' => $soNames]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 REVISI SINKRONISASI CORES: Tombol Validasi (Bandingkan QTY dengan fginvc.cntso MURNI VIA NODOC)
     */
    public function validateAppkso(Request $request)
    {
        $warehouse  = $request->warehouse;
        $operatorId = $request->operator_id;
        $docStart   = $request->doc_start;
        $docEnd     = $request->doc_end;
        $soName     = $request->so_name;

        if (empty($warehouse) || empty($operatorId) || empty($soName)) {
            return response()->json(['status' => 'error', 'message' => 'Filter belum lengkap'], 400);
        }

        try {
            // 1. Ambil data dari DB Lokal
            $picInfos = DB::table('so_all_wh_pic_stock_db')->where('no_penneng', $operatorId)->where('warehouse', $warehouse)->get();
            if ($picInfos->isEmpty()) return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);

            $lotFilters = [];
            foreach ($picInfos as $pic) {
                $gedung = strtoupper(trim($pic->gedung));
                $lotParts = explode('-', trim($pic->lot));
                $lotFilters[] = ['gedung' => $gedung, 'awal' => trim($lotParts[0]), 'akhir' => trim($lotParts[1] ?? $lotParts[0])];
            }

            $query = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')->on('a.warehouse', '=', 'm.warehouse');
                })
                ->where('a.warehouse', $warehouse)
                ->where(function ($q) use ($lotFilters) {
                    foreach ($lotFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                                ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [$filter['awal'], $filter['akhir']]);
                        });
                    }
                });

            if ($docStart && $docEnd) $query->whereBetween('a.no_doc', [$docStart, $docEnd]);

            $rows = $query->select('a.loccode as lot_display', 'a.no_doc', 'a.item', 'm.description', 'a.Rak', 'a.Qty as qty_tag')->orderBy('a.no_doc', 'asc')->get();

            // 2. Tembak DB Second (Ganti 'mysql_second' sesuai nama di config/database.php)
            // Kalau namanya beda, ganti 'mysql_second' jadi nama koneksi lo yang bener
            $cntsoData = DB::connection('fginvc')->table('cntso')
                ->where('so_name', trim($soName))
                ->select('NoDoc', DB::raw('SUM(QtyStk) as total_qty'))
                ->groupBy('NoDoc')
                ->get()
                ->keyBy(function ($item) {
                    return preg_replace('/[^A-Za-z0-9]/', '', strtoupper((string)$item->NoDoc));
                });

            $mappedRows = $rows->map(function ($row) use ($cntsoData) {
                $key = preg_replace('/[^A-Za-z0-9]/', '', strtoupper((string)$row->no_doc));
                $row->qty_appkso = isset($cntsoData[$key]) ? (int)$cntsoData[$key]->total_qty : 0;
                return $row;
            });

            return response()->json(['status' => 'success', 'master_data' => $mappedRows]);
        } catch (\Exception $e) {
            // ⚡ INI BAKAL KELUAR DI CONSOLE KALO ADA ERROR DB
            return response()->json(['status' => 'error', 'message' => 'DB ERROR: ' . $e->getMessage()], 500);
        }
    }

    public function checkDoc(Request $request)
    {
        $warehouse = $request->warehouse;

        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Gudang wajib dipilih'], 400);
        }

        try {
            $subAppkso = DB::table('so_all_wh_appkso_db')->select('nokso', 'item', DB::raw('SUM(qty) as total_qty'))->groupBy('nokso', 'item');
            $pics = DB::table('so_all_wh_pic_stock_db')->where('warehouse', $warehouse)->get();

            $picList = [];
            foreach ($pics as $p) {
                $parts = explode('-', trim($p->lot));
                $picList[] = ['nama' => $p->nama, 'gedung' => strtoupper(trim($p->gedung)), 'awal' => trim($parts[0]), 'akhir' => trim($parts[1] ?? $parts[0])];
            }

            $rows = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')->on('a.warehouse', '=', 'm.warehouse');
                })
                ->leftJoinSub($subAppkso, 'kso', function ($join) {
                    $join->on('a.no_doc', '=', 'kso.nokso')->on('a.item', '=', 'kso.item');
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

            $filteredRows = [];
            foreach ($rows as $r) {
                $selisih = $r->qty_tag - $r->qty_appkso;
                if ($selisih == 0) continue;

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

    public function getScanHistory(Request $request)
    {
        $warehouse = $request->warehouse;
        $doc = $request->doc;
        $item = $request->item;

        try {
            $history = DB::table('so_all_wh_appkso_db')
                ->where('warehouse', $warehouse)->where('nokso', $doc)->where('item', $item)->orderBy('created_at', 'desc')->get();
            return response()->json(['status' => 'success', 'data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function testValidasi(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'pesan' => 'Jalur AJAX nyambung bro!',
            'data_dummy' => [
                ['no_doc' => 'TEST-001', 'item' => 'ITEM01', 'Rak' => 1, 'qty_tag' => 100, 'qty_appkso' => 100],
                ['no_doc' => 'TEST-002', 'item' => 'ITEM02', 'Rak' => 2, 'qty_tag' => 50, 'qty_appkso' => 25]
            ]
        ]);
    }
}
