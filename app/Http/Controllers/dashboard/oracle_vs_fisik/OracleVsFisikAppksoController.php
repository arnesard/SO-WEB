<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikAppksoController extends Controller
{
    /**
     * Ambil data APPKSO per warehouse penugasan (Detail + Resume Operator + Snapshot Pattern)
     */
    public function getData(Request $request)
    {
        $warehouse = $request->query('warehouse');

        if (empty($warehouse)) {
            return response()->json([
                'detail'  => [],
                'resume'  => [],
                'pattern' => []
            ]);
        }

        // 1. Ambil Baris Detail Transaksi Utama
        $detailData = DB::table('so_all_wh_appkso_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')
                    ->on('a.warehouse', '=', 'm.warehouse');
            })
            ->where('a.warehouse', $warehouse)
            ->select(
                'a.*',
                DB::raw("IFNULL(NULLIF(m.pattern, ''), 'KOSONG / UNMAPPED') as pattern_name")
            )
            ->orderBy('a.id', 'desc')
            ->get();

        // 2. Resume Agregasi Operator Grouping Opr + Oprname
        $resumeData = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->select(
                'opr',
                'oprname',
                DB::raw('COUNT(item) as total_sku'),
                DB::raw('SUM(qty) as total_qty')
            )
            ->groupBy('opr', 'oprname')
            ->orderBy('oprname', 'asc')
            ->get();

        // 3. 🎯 OPERASI POINT 3: KUNCI AGREGASI SNAPSHOT REAL-TIME PER KOLOM PATTERN MASTER SIZE DB 🎯
        $patternData = DB::table('so_all_wh_appkso_db as a')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('a.item', '=', 'm.item')
                    ->on('a.warehouse', '=', 'm.warehouse');
            })
            ->where('a.warehouse', $warehouse)
            ->select(
                DB::raw("IFNULL(NULLIF(m.pattern, ''), 'KOSONG / UNMAPPED') as pattern_name"),
                DB::raw('COUNT(a.item) as total_sku'),
                DB::raw('SUM(a.qty) as total_qty')
            )
            ->groupBy(DB::raw("IFNULL(NULLIF(m.pattern, ''), 'KOSONG / UNMAPPED')"))
            ->orderBy('total_qty', 'desc')
            ->get();

        return response()->json([
            'detail'  => $detailData,
            'resume'  => $resumeData,
            'pattern' => $patternData
        ]);
    }

    /**
     * 🎯 IMPOR BERSIH OFFLINE: Terima Data JSON Hasil Bongkaran JavaScript Browser + Proteksi Silang 🎯
     */
    public function import(Request $request)
    {
        $warehouse  = strtoupper(trim($request->json('warehouse')));
        $sampleItem = strtoupper(trim($request->json('sample_item'))); // Tangkap sampel itemnya bro!
        $excelData  = $request->json('excel_data');

        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter Warehouse upload wajib diisi!'], 400);
        }

        if (empty($excelData) || !is_array($excelData)) {
            return response()->json(['status' => 'error', 'message' => 'Data JSON Excel kosong atau korup!'], 400);
        }

        try {
            // 🔒 🛡️ GERBANG PROTEKSI: VALIDASI SILANG ASAL-USUL FILE UTAMA LU 🛡️ 🔒
            if (!empty($sampleItem)) {
                $checkItemWarehouse = DB::table('so_all_wh_barcode_monstock_auto_db')
                    ->where('warehouse', $warehouse)
                    ->where('item', $sampleItem)
                    ->exists();

                if (!$checkItemWarehouse) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "VALIDASI REJECTED: File Excel salah comot bro! Item Ban [{$sampleItem}] kagak terdaftar di Gudang {$warehouse}. Mohon periksa kembali keselarasan berkas!"
                    ], 400);
                }
            }

            // 🪓 🎯 SIKAT KHUSUS WAREHOUSE YANG DI-UPLOAD SAJA (ANTI-DUPLIKAT DATA) 🎯 🪓
            DB::table('so_all_wh_appkso_db')->where('warehouse', $warehouse)->delete();

            $batch = [];
            $insertCount = 0;

            foreach ($excelData as $key => $row) {
                // Lewati baris indeks 0 (Judul Kolom)
                if ($key === 0) continue;

                // Pengaman: Jika kolom ITEM (index 4) kosong melompong, lewati baris ini bro!
                if (!isset($row[4]) || trim($row[4]) === '') continue;

                // Menormalisasi format tanggal verifikasi teks polosan excel
                $tglVerifikasi = null;
                if (!empty($row[8])) {
                    $tglVerifikasi = date('Y-m-d H:i:s', strtotime(trim($row[8])));
                }

                $batch[] = [
                    'warehouse'          => $warehouse,
                    'tgl'                => !empty($row[0]) ? trim($row[0]) : null,
                    'opr'                => !empty($row[1]) ? trim($row[1]) : null,
                    'oprname'            => !empty($row[2]) ? trim($row[2]) : null,
                    'nokso'              => !empty($row[3]) ? trim($row[3]) : null,
                    'item'               => strtoupper(trim($row[4])),
                    'deskripsi'          => !empty($row[5]) ? trim($row[5]) : null,
                    'qty'                => isset($row[6]) ? intval(trim($row[6])) : 0,
                    'verifikasi_nama'    => !empty($row[7]) ? trim($row[7]) : null,
                    'tanggal_verifikasi' => $tglVerifikasi,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];

                if (count($batch) >= 500) {
                    DB::table('so_all_wh_appkso_db')->insert($batch);
                    $insertCount += count($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('so_all_wh_appkso_db')->insert($batch);
                $insertCount += count($batch);
            }

            return response()->json([
                'status' => 'success',
                'message' => "Sukses membersihkan data lama & suntik masal {$insertCount} baris data APPKSO baru ke Gudang {$warehouse}!",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error SQL local database: ' . $e->getMessage()
            ], 500);
        }
    }
}
