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
     * Ambil daftar operator stock lengkap dengan membawa payload gedung dan lot asli dari DB
     */
    public function getOperatorsByWarehouse(Request $request)
    {
        $warehouse = $request->query('warehouse');
        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Warehouse wajib dipilih bro!'], 400);
        }

        try {
            $operators = DB::table('so_all_wh_pic_stock_db')
                ->where('warehouse', $warehouse)
                ->select('id', 'no_penneng', 'nama', 'gedung', 'lot')
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
     * 🎯 SINKRONISASI CORES: Ambil data agregasi murni dari struktur table auto fisik baru
     */
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

            $picInfo = DB::table('so_all_wh_pic_stock_db')
                ->where('id', $operatorId)
                ->first();

            if (!$picInfo) {
                return response()->json(['status' => 'error', 'message' => 'PIC tidak ditemukan'], 404);
            }

            $gedung = strtoupper(trim($picInfo->gedung));
            $lotRaw = trim($picInfo->lot);

            $lotParts = explode('-', $lotRaw);
            $lotAwal  = trim($lotParts[0]);
            $lotAkhir = trim($lotParts[1] ?? $lotAwal);

            // =========================
            // 🔥 QUERY START (INI FIX UTAMA)
            // =========================
            $query = DB::table('so_all_wh_barcode_monstock_auto_db as a')
                ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                    $join->on('a.item', '=', 'm.item')
                        ->on('a.warehouse', '=', 'm.warehouse');
                })
                ->where('a.warehouse', $warehouse)
                ->where('a.loccode', 'LIKE', $gedung . '-%')
                ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [$lotAwal, $lotAkhir]);

            // =========================
            // 🔥 FILTER DOC RANGE (INI TAMBAHAN LU)
            // =========================
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
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getDocuments(Request $request)
    {
        $operatorId = $request->query('operator_id');

        $pic = DB::table('so_all_wh_pic_stock_db')
            ->where('id', $operatorId)
            ->first();

        if (!$pic) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIC tidak ditemukan'
            ]);
        }

        $docs = DB::table('so_all_wh_barcode_monstock_auto_db')
            ->where('warehouse', $pic->warehouse)
            ->where('loccode', 'LIKE', strtoupper($pic->gedung) . '-%')
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
        $operatorId  = $request->operator_id;
        $docStart    = $request->doc_start;
        $docEnd      = $request->doc_end;

        // Ambil PIC
        $picInfo = DB::table('so_all_wh_pic_stock_db')
            ->where('id', $operatorId)
            ->first();

        if (!$picInfo) {
            return back()->with('error', 'PIC tidak ditemukan');
        }

        $gedung = strtoupper(trim($picInfo->gedung));
        $lotRaw = trim($picInfo->lot);

        $lotParts = explode('-', $lotRaw);
        $lotAwal  = $lotParts[0];
        $lotAkhir = $lotParts[1] ?? $lotAwal;

        // Query utama (Memilah data berdasarkan lot dan doc range)
        $rows = DB::table('so_all_wh_barcode_monstock_auto_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')
                    ->on('a.warehouse', '=', 'm.warehouse');
            })
            ->where('a.warehouse', $warehouse)
            ->where('a.loccode', 'LIKE', $gedung . '-%')
            ->whereBetween(DB::raw("SUBSTRING_INDEX(a.loccode, '-', -1)"), [$lotAwal, $lotAkhir])
            ->when($docStart && $docEnd, function ($q) use ($docStart, $docEnd) {
                $q->whereBetween('a.no_doc', [$docStart, $docEnd]);
            })
            ->select(
                'a.no_doc',
                'a.item',
                'a.loccode',
                'a.Rak',
                'a.Qty',
                'm.description as description_master' // 🎯 Tarik nama deskripsi asli dari master size
            )
            ->orderBy('a.no_doc')
            ->get();

        // Mapping Data Fix (Data description diambil langsung dari field SQL hasil join)
        $data = $rows->map(function ($t) {
            // Logika pembagian grade sederhana (Misal akhiran item membedakan OE / OK)
            $isOe = str_ends_with($t->item, '0');

            return [
                'noDoc'       => $t->no_doc,
                'item'        => $t->item,
                'description' => $t->description_master ?? '-', // 🎯 FIX: Masukkan data deskripsi asli ke sini bro!
                'rackcode'    => $t->Rak ?? 0,
                'oe'          => $isOe ? ($t->Qty ?? 0) : 0,
                'ok'          => !$isOe ? ($t->Qty ?? 0) : 0,
                'loccode'     => $t->loccode ?? '-',
            ];
        });

        return view('dashboard.oracle_vs_fisik.tag_stock_rev', [
            'rows'   => $data,
            'pic'    => $picInfo->nama,
            'gedung' => $picInfo->gedung,
            'lot'    => $picInfo->lot,
        ]);
    }
}
