<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OracleVsFisikSnapshotController extends Controller
{
    /**
     * Mengambil daftar gudang unik dari Snapshot untuk dropdown filter
     */
    public function getWarehouseList()
    {
        $warehouses = DB::table('so_all_wh_snapshot_db')
            ->select('warehouse')
            ->distinct()
            ->whereNotNull('warehouse')
            ->orderBy('warehouse', 'asc')
            ->pluck('warehouse');

        return response()->json([
            'success' => true,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Mengambil data untuk ditampilkan di Datatable Frontend
     */
    public function getData(Request $request)
    {
        $warehouse = $request->input('warehouse');
        $search = $request->input('search');

        $query = DB::table('so_all_wh_snapshot_db as snap')
            ->leftJoin('so_all_wh_master_size_db as master', function ($join) {
                $join->on('snap.item', '=', 'master.item')
                    ->on('snap.warehouse', '=', 'master.warehouse');
            })
            ->select(
                'snap.id',
                'snap.warehouse',
                'snap.item',
                'snap.qty',
                'master.description'
            );

        if (!empty($warehouse)) {
            $query->where('snap.warehouse', $warehouse);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('snap.item', 'LIKE', "%{$search}%")
                    ->orWhere('master.description', 'LIKE', "%{$search}%");
            });
        }

        // 🎯 FIX: ->limit(500) dihilangkan agar tampil 100% full barisnya 🎯
        $data = $query->orderBy('snap.updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Menerima payload JSON (hasil parse Javascript) dan Batch Insert ke DB
     */
    public function importExcel(Request $request)
    {
        $warehouse = strtoupper(trim($request->json('warehouse')));
        $sampleItem = strtoupper(trim($request->json('sample_item'))); // Ambil sampel 1 item
        $excelData = $request->json('excel_data');

        if (empty($warehouse)) {
            return response()->json(['success' => false, 'message' => 'Target gudang wajib diisi bro!'], 400);
        }

        if (empty($excelData) || !is_array($excelData)) {
            return response()->json(['success' => false, 'message' => 'Data JSON dari browser kosong / korup!'], 400);
        }

        try {
            // 🛡️ GERBANG PROTEKSI: Validasi silang item excel terhadap Gudang di Master Size 🛡️
            if (!empty($sampleItem)) {
                $checkItemWarehouse = DB::table('so_all_wh_master_size_db')
                    ->where('warehouse', $warehouse)
                    ->where('item', $sampleItem)
                    ->exists();

                if (!$checkItemWarehouse) {
                    return response()->json([
                        'success' => false,
                        'message' => "VALIDASI REJECTED: Item sampel [{$sampleItem}] tidak terdaftar di Gudang {$warehouse} pada Master Size DB. Pastikan file Excel sesuai dengan gudang tujuan!"
                    ], 400);
                }
            }

            DB::transaction(function () use ($warehouse, $excelData, &$insertCount) {
                // Bersihin data lama per gudang biar datanya fresh replace
                DB::table('so_all_wh_snapshot_db')->where('warehouse', $warehouse)->delete();

                $batch = [];
                $insertCount = 0;

                foreach ($excelData as $key => $row) {
                    if ($key === 0) continue; // Skip header

                    $item = trim($row[0] ?? '');
                    if (empty($item)) continue;

                    $rawQty = str_replace([',', ' '], '', (string)($row[1] ?? '0'));
                    $qty = (int)$rawQty;

                    $batch[] = [
                        'warehouse'  => $warehouse,
                        'item'       => strtoupper($item),
                        'qty'        => $qty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        DB::table('so_all_wh_snapshot_db')->insert($batch);
                        $insertCount += count($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    DB::table('so_all_wh_snapshot_db')->insert($batch);
                    $insertCount += count($batch);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Beres bro! {$insertCount} baris data Snapshot berhasil disuntik ke Gudang {$warehouse}."
            ]);
        } catch (\Exception $e) {
            Log::error('Upload Snapshot Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal Insert DB: ' . $e->getMessage()
            ], 500);
        }
    }
}
