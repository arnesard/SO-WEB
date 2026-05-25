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
        $warehouse = $request->input('warehouse');
        $rows = $request->input('rows');

        if (!$rows || count($rows) <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'File Excel kosong atau tidak terbaca bro!'
            ]);
        }

        $batch = 'NB-' . now()->format('YmdHis');
        $insertData = [];

        foreach (array_slice($rows, 1) as $row) {
            if (empty($row) || !isset($row[0])) continue;

            $insertData[] = [
                'warehouse'    => $warehouse,
                'rackcode'     => $row[0] ?? null,
                'item'         => $row[1] ?? null,
                'qty'          => (int)($row[4] ?? 0),
                'oem'          => (int)($row[10] ?? 0),
                'loccode'      => $row[13] ?? null,
                'upload_batch' => $batch,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if (empty($insertData)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada baris data valid untuk di-insert']);
        }

        try {
            $chunks = array_chunk($insertData, 200);
            foreach ($chunks as $chunk) {
                DB::table('so_all_wh_non_barcode_tagstock_db')->insert($chunk);
            }

            return response()->json([
                'status' => 'success',
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
            $operators = DB::table('so_all_wh_non_barcode_tagstock_db')
                ->where('warehouse', $warehouse)
                ->whereNotNull('oem')
                ->select(
                    'oem as no_penneng',
                    DB::raw("'OPERATOR NON-BARCODE' as nama"),
                    DB::raw("'AREA' as gedung"),
                    DB::raw("GROUP_CONCAT(DISTINCT rackcode ORDER BY rackcode ASC SEPARATOR ', ') as combined_lot")
                )
                ->groupBy('oem')
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
        $warehouse = $request->warehouse;
        $operator = $request->operator_id;
        $docStart = $request->doc_start;
        $docEnd = $request->doc_end;

        $query = DB::table('so_all_wh_non_barcode_tagstock_db')
            ->where('warehouse', $warehouse)
            ->where('oem', $operator);

        if ($docStart && $docEnd) {
            $query->whereBetween('upload_batch', [$docStart, $docEnd]);
        }

        $data = $query->get()->map(function ($row) {
            return [
                'id' => $row->id,
                'lot_display' => $row->rackcode,
                'no_doc' => $row->upload_batch,
                'item' => $row->item,
                'description' => 'Item Non-Barcode ' . $row->item,
                'Rak' => 1,
                'Qty' => $row->qty
            ];
        });

        return response()->json([
            'status' => 'success',
            'master_data' => $data
        ]);
    }
}
