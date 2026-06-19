<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// NAMA CLASS WAJIB SAMA DENGAN NAMA FILE NYA BRO!
class OracleVsFisikNonBarcodeTagStockController extends Controller
{
    public function uploadExcel(Request $request)
    {
        $warehouse = $request->input('warehouse') ?? $request->json('warehouse');
        $rows      = $request->input('rows') ?? $request->json('rows');

        if (!$rows || count($rows) <= 1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'File Excel kosong atau tidak terbaca bro!'
            ]);
        }

        $insertData = [];

        // 🎯 Ambil histori no_doc lama dari DB (loccode@item → no_doc)
        // Sama persis dengan algoritma Barcode Monstock
        $historicalDocMap = DB::table('so_all_wh_non_barcode_tagstock_db')
            ->where('warehouse', $warehouse)
            ->whereNotNull('upload_batch')
            ->where('upload_batch', '!=', '')
            ->select('loccode', 'item', 'upload_batch')
            ->distinct()
            ->get()
            ->mapWithKeys(function ($r) {
                $key = strtoupper(trim($r->loccode)) . '@' . strtoupper(trim($r->item));
                return [$key => $r->upload_batch];
            })->toArray();

        $maxSequencePerPrefix = [];

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row) || !isset($row[0])) continue;

            $item    = strtoupper(trim($row[1] ?? ''));
            $loccode = strtoupper(trim($row[13] ?? ''));
            $qty     = (int)($row[4] ?? 0);
            $oem     = (int)($row[10] ?? 0);
            $rackcode = $row[0] ?? null;

            // B. LOGIKA BYPASS GRADE OEM DAN SPLIT ITEM (-0 / -1)
            $isForcedOem = (substr($item, 0, 2) === 'TH' || substr($item, -2) === 'SP');

            $rawSplitRecords = [];
            if ($isForcedOem) {
                $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $qty, 'oem' => $qty];
            } else {
                if ($oem == $qty) {
                    $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $oem, 'oem' => $oem];
                } elseif ($oem == 0) {
                    $rawSplitRecords[] = ['item' => $item . '-1', 'qty' => $qty, 'oem' => 0];
                } else {
                    $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $oem, 'oem' => $oem];
                    $rawSplitRecords[] = ['item' => $item . '-1', 'qty' => ($qty - $oem), 'oem' => 0];
                }
            }

            foreach ($rawSplitRecords as $split) {
                $splitItem = $split['item'];

                // Generate no_doc per baris berdasarkan loccode & splitItem
                if (empty($loccode) || $loccode === '-' || $loccode === '~') {
                    $noDoc = $loccode ?: '-';
                } else {
                    $historyKey = $loccode . '@' . $splitItem;

                    if (isset($historicalDocMap[$historyKey])) {
                        // Pakai no_doc lama supaya konsisten
                        $noDoc = $historicalDocMap[$historyKey];
                    } else {
                        // Generate baru: G + char ke-5 dari loccode + suffix setelah char ke-6 + 2-digit sequence
                        $prefixDoc   = 'G';
                        $char5       = (strlen($loccode) >= 5) ? substr($loccode, 4, 1) : '0';
                        $suffixLoc   = (strlen($loccode) >= 7) ? substr($loccode, 6) : 'UNKNOWN';
                        $prefixNoDoc = $prefixDoc . $char5 . $suffixLoc;

                        if (!isset($maxSequencePerPrefix[$prefixNoDoc])) {
                            $maxSequencePerPrefix[$prefixNoDoc] = 0;

                            foreach ($historicalDocMap as $oldDoc) {
                                if (strpos($oldDoc, $prefixNoDoc) === 0) {
                                    $seqNumber = intval(substr($oldDoc, -2));
                                    if ($seqNumber > $maxSequencePerPrefix[$prefixNoDoc]) {
                                        $maxSequencePerPrefix[$prefixNoDoc] = $seqNumber;
                                    }
                                }
                            }
                        }

                        $maxSequencePerPrefix[$prefixNoDoc]++;
                        $noDoc = $prefixNoDoc . str_pad($maxSequencePerPrefix[$prefixNoDoc], 2, '0', STR_PAD_LEFT);

                        $historicalDocMap[$historyKey] = $noDoc;
                    }
                }

                $insertData[] = [
                    'warehouse'    => $warehouse,
                    'rackcode'     => $rackcode,
                    'item'         => $splitItem,
                    'qty'          => $split['qty'],
                    'oem'          => $split['oem'],
                    'loccode'      => $loccode,
                    'upload_batch' => $noDoc,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }

        if (empty($insertData)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada baris data valid untuk di-insert']);
        }

        try {
            // 🎯 BILAS DATA LAMA — Hapus semua data lama untuk warehouse ini
            // Sama persis dengan pola Barcode Monstock
            DB::table('so_all_wh_non_barcode_tagstock_db')
                ->where('warehouse', $warehouse)
                ->delete();

            // Insert data baru
            $chunks = array_chunk($insertData, 200);
            foreach ($chunks as $chunk) {
                DB::table('so_all_wh_non_barcode_tagstock_db')->insert($chunk);
            }

            return response()->json([
                'status'   => 'success',
                'inserted' => count($insertData)
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal simpan ke DB: ' . $e->getMessage()], 500);
        }
    }

    public function initFilters()
    {
        $warehouses = DB::table('so_all_wh_non_barcode_tagstock_db')
            ->whereNotNull('warehouse')
            ->distinct()
            ->orderBy('warehouse')
            ->pluck('warehouse');

        return response()->json([
            'status' => 'success',
            'warehouses' => $warehouses
        ]);
    }

    public function getOperatorsByWarehouse(Request $request)
    {
        $warehouse = $request->query('warehouse');
        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Warehouse wajib dipilih!'], 400);
        }

        try {
            // SESUDAH ✅
            $operators = DB::table('so_all_wh_pic_stock_db as p')
                ->where('p.warehouse', $warehouse)
                ->whereNotNull('p.no_penneng')
                ->where('p.no_penneng', '!=', '')
                ->whereExists(function ($sub) use ($warehouse) {
                    $sub->select(DB::raw(1))
                        ->from('so_all_wh_non_barcode_tagstock_db as nb')
                        ->where('nb.warehouse', $warehouse)
                        ->where(function ($q) {
                            $q->whereRaw("nb.loccode LIKE CONCAT(UPPER(TRIM(p.gedung)), '-%')")
                                ->whereRaw("SUBSTRING_INDEX(nb.loccode, '-', -1) BETWEEN
                      SUBSTRING_INDEX(p.lot, '-', 1)
                      AND SUBSTRING_INDEX(p.lot, '-', -1)");
                        });
                })
                ->select(
                    'p.no_penneng',
                    'p.nama',
                    'p.gedung',
                    DB::raw("GROUP_CONCAT(p.lot ORDER BY p.lot ASC SEPARATOR ', ') as combined_lot")
                )
                ->groupBy('p.no_penneng', 'p.nama', 'p.gedung')
                ->orderBy('p.nama', 'asc')
                ->get();
            return response()->json([
                'status' => 'success',
                'operators' => $operators
            ]);
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
            // 1. Ambil PIC
            $picInfos = DB::table('so_all_wh_pic_stock_db')
                ->where('no_penneng', $operatorId)
                ->where('warehouse', $warehouse)
                ->get();

            if ($picInfos->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);
            }

            // 2. Lot filters
            $lotFilters = [];
            foreach ($picInfos as $pic) {
                $gedung   = strtoupper(trim($pic->gedung));
                $lotParts = explode('-', trim($pic->lot));
                $lotFilters[] = [
                    'gedung' => $gedung,
                    'awal'   => trim($lotParts[0]),
                    'akhir'  => trim($lotParts[1] ?? $lotParts[0]),
                ];
            }

            // 3. Subquery appkso
            $subAppkso = DB::table('so_all_wh_appkso_db')
                ->select(
                    DB::raw('TRIM(UPPER(nokso)) as nokso'),
                    DB::raw('TRIM(UPPER(item)) as item'),
                    DB::raw('SUM(qty) as total_qty')
                )
                ->groupBy('nokso', 'item');

            // 4. Subquery RAK — hitung COUNT(DISTINCT rackcode) per loccode+item TANPA GROUP BY di query utama
            $subRak = DB::table('so_all_wh_non_barcode_tagstock_db')
                ->select(
                    'warehouse',
                    'loccode',
                    'item',
                    DB::raw('COUNT(DISTINCT rackcode) as jumlah_rak')
                )
                ->where('warehouse', $warehouse)
                ->groupBy('warehouse', 'loccode', 'item');

            // 5. Query utama
            $rows = DB::table('so_all_wh_non_barcode_tagstock_db as t')

                ->leftJoinSub($subAppkso, 'a', function ($join) {
                    $join->on(DB::raw('TRIM(UPPER(t.upload_batch))'), '=', 'a.nokso')
                        ->on(DB::raw('TRIM(UPPER(t.item))'), '=', 'a.item');
                })

                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('t.item', '=', 'm.item')
                        ->on('t.warehouse', '=', 'm.warehouse');
                })

                // 🔥 JOIN subquery RAK
                ->leftJoinSub($subRak, 'r', function ($join) {
                    $join->on('t.warehouse', '=', 'r.warehouse')
                        ->on('t.loccode', '=', 'r.loccode')
                        ->on('t.item', '=', 'r.item');
                })

                ->where('t.warehouse', $warehouse)

                ->where(function ($q) use ($lotFilters) {
                    foreach ($lotFilters as $filter) {
                        $q->orWhere(function ($sub) use ($filter) {
                            $sub->where('t.loccode', 'LIKE', $filter['gedung'] . '-%')
                                ->whereBetween(DB::raw("SUBSTRING_INDEX(t.loccode, '-', -1)"), [
                                    $filter['awal'],
                                    $filter['akhir'],
                                ]);
                        });
                    }
                })

                ->when($docStart && $docEnd, function ($q) use ($docStart, $docEnd) {
                    $q->whereBetween('t.upload_batch', [$docStart, $docEnd]);
                })

                ->select(
                    't.upload_batch as no_doc',
                    't.loccode',
                    't.item',
                    'm.description as description',
                    'r.jumlah_rak as Rak',                  // ✅ dari subquery
                    DB::raw('SUM(t.qty) as qty'),            // ✅ total qty
                    DB::raw('MAX(a.total_qty) as actual_qty'),
                    DB::raw("
                    CASE
                        WHEN MAX(a.total_qty) IS NULL THEN 'BELUM'
                        WHEN SUM(t.qty) = MAX(a.total_qty) THEN 'SESUAI'
                        ELSE 'TIDAK SESUAI'
                    END as status_validasi
                ")
                )

                ->groupBy('t.upload_batch', 't.loccode', 't.item', 'm.description', 'r.jumlah_rak')
                ->orderBy('t.upload_batch', 'asc')
                ->get();

            $data = $rows->map(function ($row) {
                return [
                    'lot_display'     => $row->loccode,
                    'no_doc'          => $row->no_doc,
                    'item'            => $row->item,
                    'description'     => $row->description,
                    'Rak'             => $row->Rak,
                    'Qty'             => $row->qty,
                    'actual_qty'      => $row->actual_qty,
                    'status_validasi' => $row->status_validasi,
                ];
            });

            return response()->json([
                'status'      => 'success',
                'master_data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    public function printTagStock(Request $request)
    {
        $warehouse  = $request->warehouse;
        $operatorId = $request->operator_id;
        $docStart   = $request->doc_start;
        $docEnd     = $request->doc_end;

        if (empty($warehouse) || empty($operatorId)) {
            return back()->with('error', 'Filter belum lengkap');
        }

        $picInfos = DB::table('so_all_wh_pic_stock_db')
            ->where('no_penneng', $operatorId)
            ->where('warehouse', $warehouse)
            ->get();

        if ($picInfos->isEmpty()) {
            return back()->with('error', 'PIC tidak ditemukan');
        }

        $lotFilters = [];
        foreach ($picInfos as $pic) {
            $gedung   = strtoupper(trim($pic->gedung));
            $lotParts = explode('-', trim($pic->lot));
            $lotFilters[] = [
                'gedung' => $gedung,
                'awal'   => trim($lotParts[0]),
                'akhir'  => trim($lotParts[1] ?? $lotParts[0]),
            ];
        }

        // 🔥 Subquery RAK — hitung COUNT(DISTINCT rackcode) per loccode+item
        $subRak = DB::table('so_all_wh_non_barcode_tagstock_db')
            ->select(
                'warehouse',
                'loccode',
                'item',
                DB::raw('COUNT(DISTINCT rackcode) as jumlah_rak')
            )
            ->where('warehouse', $warehouse)
            ->groupBy('warehouse', 'loccode', 'item');

        $rows = DB::table('so_all_wh_non_barcode_tagstock_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')
                    ->on('a.warehouse', '=', 'm.warehouse');
            })
            // 🔥 JOIN subquery RAK
            ->leftJoinSub($subRak, 'r', function ($join) {
                $join->on('a.warehouse', '=', 'r.warehouse')
                    ->on('a.loccode', '=', 'r.loccode')
                    ->on('a.item', '=', 'r.item');
            })
            ->where('a.warehouse', $warehouse)
            ->where(function ($q) use ($lotFilters) {
                foreach ($lotFilters as $filter) {
                    $q->orWhere(function ($sub) use ($filter) {
                        $sub->where('a.loccode', 'LIKE', $filter['gedung'] . '-%')
                            ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [
                                $filter['awal'],
                                $filter['akhir'],
                            ]);
                    });
                }
            })
            ->when($docStart && $docEnd, function ($q) use ($docStart, $docEnd) {
                $q->whereBetween('a.upload_batch', [$docStart, $docEnd]);
            })
            ->select(
                'a.upload_batch as no_doc',
                'a.item',
                'a.loccode',
                'm.description as description_master',
                'r.jumlah_rak as rak_count',        // ✅ jumlah rak unik
                DB::raw('SUM(a.qty) as total_qty'),  // ✅ qty digabung
                DB::raw("
                CASE
                    WHEN RIGHT(a.item, 1) = '0' THEN SUM(a.qty)
                    ELSE 0
                END as oe_qty
            "),
                DB::raw("
                CASE
                    WHEN RIGHT(a.item, 1) != '0' THEN SUM(a.qty)
                    ELSE 0
                END as ok_qty
            ")
            )
            ->groupBy('a.upload_batch', 'a.loccode', 'a.item', 'm.description', 'r.jumlah_rak')
            ->orderBy('a.upload_batch', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan untuk dicetak');
        }

        $data = $rows->map(function ($t) {
            return [
                'noDoc'       => $t->no_doc,
                'item'        => $t->item,
                'description' => $t->description_master ?? '-',
                'rackcode'    => $t->rak_count ?? 1,   // ✅ jumlah rak
                'oe'          => $t->oe_qty ?? 0,
                'ok'          => $t->ok_qty ?? 0,
                'loccode'     => $t->loccode ?? '-',
            ];
        });

        $picNameDisplay = $picInfos->first()->nama;
        $gedungDisplay  = $picInfos->pluck('gedung')->unique()->implode(', ');
        $lotDisplay     = $picInfos->pluck('lot')->implode(', ');

        return view('dashboard.oracle_vs_fisik.tag_stock_rev', [
            'rows'   => $data,
            'pic'    => $picNameDisplay,
            'gedung' => $gedungDisplay,
            'lot'    => $lotDisplay,
        ]);
    }
}
