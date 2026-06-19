<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SnapShotController extends Controller
{
    /**
     * Halaman utama Snapshot BPW
     */
    public function index()
    {
        return view('appkso.snapshot');
    }

    /**
     * Ambil daftar SO Name unik dari tabel cntso untuk dropdown
     */
    public function getSoNames()
    {
        try {
            $soNames = DB::connection('fginvc')
                ->table('cntso')
                ->whereNotNull('so_name')
                ->where('so_name', '!=', '')
                ->select('so_name', DB::raw('MAX(recid) as last_id'))
                ->groupBy('so_name')
                ->orderByDesc('last_id')
                ->pluck('so_name');

            return response()->json([
                'success' => true,
                'so_names' => $soNames
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Ambil data snapshot_bpw dengan filter so_name & search
     */
    public function getData(Request $request)
    {
        $soName = $request->query('so_name');
        $search = $request->query('search', '');

        if (empty($soName)) {
            return response()->json(['success' => false, 'message' => 'SO Name wajib dipilih'], 400);
        }

        try {
            $query = DB::table('snapshot_bpw as s')
                ->leftJoin('so_all_wh_master_size_db as m', 's.ItemCode', '=', 'm.item')
                ->where('s.so_name', $soName);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('s.ItemCode', 'LIKE', '%' . $search . '%')
                        ->orWhere('m.description', 'LIKE', '%' . $search . '%');
                });
            }

            $data = $query->select(
                's.so_name',
                's.ItemCode as item',
                'm.description',
                's.QtyStk as qty'
            )
                ->orderBy('s.ItemCode', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Import Excel: Hapus data lama by so_name, insert baru
     */
    public function import(Request $request)
    {
        $soName    = $request->input('so_name');
        $excelData = $request->input('excel_data', []);

        if (empty($soName)) {
            return response()->json(['success' => false, 'message' => 'SO Name wajib dipilih!'], 400);
        }

        if (count($excelData) <= 1) {
            return response()->json(['success' => false, 'message' => 'Data Excel kosong bro!'], 400);
        }

        try {
            DB::beginTransaction();

            // Hapus data lama berdasarkan so_name
            DB::table('snapshot_bpw')->where('so_name', $soName)->delete();

            $insertBatch = [];
            $now         = now();

            // Skip baris pertama (header), mulai dari index 1
            for ($i = 1; $i < count($excelData); $i++) {
                $row      = $excelData[$i];
                $itemCode = isset($row[0]) ? trim($row[0]) : null;
                $qtyStk   = isset($row[1]) ? intval($row[1]) : 0;

                if (empty($itemCode)) continue;

                $insertBatch[] = [
                    'so_name'    => $soName,
                    'ItemCode'   => strtoupper($itemCode),
                    'QtyStk'     => $qtyStk,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Insert per 500 baris agar tidak overload
                if (count($insertBatch) >= 500) {
                    DB::table('snapshot_bpw')->insert($insertBatch);
                    $insertBatch = [];
                }
            }

            if (!empty($insertBatch)) {
                DB::table('snapshot_bpw')->insert($insertBatch);
            }

            DB::commit();

            $totalInserted = count($excelData) - 1;

            return response()->json([
                'success' => true,
                'message' => "Berhasil import {$totalInserted} baris data untuk SO: {$soName}!"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
