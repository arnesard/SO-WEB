<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikAppksoController extends Controller
{
    /**
     * ============================================================
     * PATCH CONTROLLER
     * File: app/Http/Controllers/dashboard/oracle_vs_fisik/OracleVsFisikAppksoController.php
     *
     * INSTRUKSI:
     * Ganti method getData() yang lama dengan method di bawah ini.
     * Method lain (import, getWarehouseList) TIDAK DIUBAH.
     * ============================================================
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

        // Ambil semua data appkso + pattern
        $rawDetail = DB::table('so_all_wh_appkso_db as a')
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

        // Ambil semua auditor untuk warehouse ini
        $auditorList = DB::table('so_all_wh_pic_auditor_db')
            ->where('warehouse', $warehouse)
            ->select('nama', 'gedung', 'lot')
            ->get();

        // Helper: parse lot range "B01-B65" → ['letter' => 'B', 'from' => 1, 'to' => 65]
        $parseLotRange = function ($lotStr) {
            // Format: "B01-B65" atau "A01-A29"
            if (preg_match('/^([A-Z]+)(\d+)-[A-Z]+(\d+)$/', trim($lotStr), $m)) {
                return [
                    'letter' => $m[1],
                    'from'   => (int) $m[2],
                    'to'     => (int) $m[3],
                ];
            }
            return null;
        };

        // Helper: parse nokso "G2B2401" → ['gedung' => 'BPW02', 'letter' => 'B', 'number' => 24]
        $parseNokso = function ($nokso) {
            // Format: G{digit}{letter}{number}{urutan}
            // G2B2401 → gedung=BPW02, letter=B, number=24
            if (preg_match('/^G(\d+)([A-Z]+)(\d{2})\d{2}$/', trim($nokso), $m)) {
                return [
                    'gedung' => 'BPW0' . $m[1],
                    'letter' => $m[2],
                    'number' => (int) $m[3],
                ];
            }
            return null;
        };

        // Helper: cari nama auditor berdasarkan nokso
        $findAuditor = function ($nokso) use ($auditorList, $parseLotRange, $parseNokso) {
            $parsed = $parseNokso($nokso);
            if (!$parsed) return '-';

            foreach ($auditorList as $aud) {
                if (trim($aud->gedung) !== $parsed['gedung']) continue;
                $range = $parseLotRange($aud->lot);
                if (!$range) continue;
                if (
                    $range['letter'] === $parsed['letter'] &&
                    $parsed['number'] >= $range['from'] &&
                    $parsed['number'] <= $range['to']
                ) {
                    return trim($aud->nama);
                }
            }
            return '-';
        };

        // Inject auditor_nama ke setiap baris detail
        $detailData = $rawDetail->map(function ($row) use ($findAuditor) {
            $row->auditor_nama = $findAuditor($row->nokso ?? '');
            return $row;
        });

        // Resume Operator
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

        // Snapshot Pattern
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
    // public function import(Request $request)
    // {
    //     $warehouse  = strtoupper(trim($request->json('warehouse')));
    //     $sampleItem = strtoupper(trim($request->json('sample_item'))); // Tangkap sampel itemnya bro!
    //     $excelData  = $request->json('excel_data');

    //     if (empty($warehouse)) {
    //         return response()->json(['status' => 'error', 'message' => 'Parameter Warehouse upload wajib diisi!'], 400);
    //     }

    //     if (empty($excelData) || !is_array($excelData)) {
    //         return response()->json(['status' => 'error', 'message' => 'Data JSON Excel kosong atau korup!'], 400);
    //     }

    //     try {
    //         // 🔒 🛡️ GERBANG PROTEKSI: VALIDASI SILANG ASAL-USUL FILE UTAMA LU 🛡️ 🔒
    //         if (!empty($sampleItem)) {
    //             $checkItemWarehouse = DB::table('so_all_wh_barcode_monstock_auto_db')
    //                 ->where('warehouse', $warehouse)
    //                 ->where('item', $sampleItem)
    //                 ->exists();

    //             if (!$checkItemWarehouse) {
    //                 return response()->json([
    //                     'status'  => 'error',
    //                     'message' => "VALIDASI REJECTED: File Excel salah comot bro! Item Ban [{$sampleItem}] kagak terdaftar di Gudang {$warehouse}. Mohon periksa kembali keselarasan berkas!"
    //                 ], 400);
    //             }
    //         }

    //         // 🪓 🎯 SIKAT KHUSUS WAREHOUSE YANG DI-UPLOAD SAJA (ANTI-DUPLIKAT DATA) 🎯 🪓
    //         DB::table('so_all_wh_appkso_db')->where('warehouse', $warehouse)->delete();

    //         $batch = [];
    //         $insertCount = 0;

    //         foreach ($excelData as $key => $row) {
    //             // Lewati baris indeks 0 (Judul Kolom)
    //             if ($key === 0) continue;

    //             // Pengaman: Jika kolom ITEM (index 4) kosong melompong, lewati baris ini bro!
    //             if (!isset($row[4]) || trim($row[4]) === '') continue;

    //             // Menormalisasi format tanggal verifikasi teks polosan excel
    //             $tglVerifikasi = null;
    //             if (!empty($row[8])) {
    //                 $tglVerifikasi = date('Y-m-d H:i:s', strtotime(trim($row[8])));
    //             }

    //             $batch[] = [
    //                 'warehouse'          => $warehouse,
    //                 'tgl'                => !empty($row[0]) ? trim($row[0]) : null,
    //                 'opr'                => !empty($row[1]) ? trim($row[1]) : null,
    //                 'oprname'            => !empty($row[2]) ? trim($row[2]) : null,
    //                 'nokso'              => !empty($row[3]) ? trim($row[3]) : null,
    //                 'item'               => strtoupper(trim($row[4])),
    //                 'deskripsi'          => !empty($row[5]) ? trim($row[5]) : null,
    //                 'qty'                => isset($row[6]) ? intval(trim($row[6])) : 0,
    //                 'verifikasi_nama'    => !empty($row[7]) ? trim($row[7]) : null,
    //                 'tanggal_verifikasi' => $tglVerifikasi,
    //                 'created_at'         => now(),
    //                 'updated_at'         => now(),
    //             ];

    //             if (count($batch) >= 500) {
    //                 DB::table('so_all_wh_appkso_db')->insert($batch);
    //                 $insertCount += count($batch);
    //                 $batch = [];
    //             }
    //         }

    //         if (!empty($batch)) {
    //             DB::table('so_all_wh_appkso_db')->insert($batch);
    //             $insertCount += count($batch);
    //         }

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Sukses membersihkan data lama & suntik masal {$insertCount} baris data APPKSO baru ke Gudang {$warehouse}!",
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Error SQL local database: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
    public function import(Request $request)
    {
        $warehouse  = strtoupper(trim($request->json('warehouse')));
        $sampleItem = strtoupper(trim($request->json('sample_item')));
        $excelData  = $request->json('excel_data');

        if (empty($warehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter Warehouse upload wajib diisi!'], 400);
        }

        if (empty($excelData) || !is_array($excelData)) {
            return response()->json(['status' => 'error', 'message' => 'Data JSON Excel kosong atau korup!'], 400);
        }

        try {
            // 🔒 GERBANG PROTEKSI: VALIDASI SILANG ASAL-USUL FILE UTAMA
            if (!empty($sampleItem)) {
                $checkItemWarehouse = DB::table('so_all_wh_barcode_monstock_auto_db')
                    ->where('warehouse', $warehouse)
                    ->where('item', $sampleItem)
                    ->exists();

                if (!$checkItemWarehouse) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "VALIDASI REJECTED: File Excel salah comot bro! Item Ban [{$sampleItem}] kagak terdaftar di Gudang {$warehouse}."
                    ], 400);
                }
            }

            // 🪓 HAPUS DATA LAMA UNTUK WAREHOUSE TERKAIT
            DB::table('so_all_wh_appkso_db')->where('warehouse', $warehouse)->delete();

            $batch = [];
            $insertCount = 0;

            // 🛠️ TRACKING ARRAY: Menampung gabungan key unik (nokso + item + qty)
            $uniqueKeys = [];

            foreach ($excelData as $key => $row) {
                // Lewati baris indeks 0 (Judul Kolom)
                if ($key === 0) continue;

                // Pengaman: Jika kolom ITEM (index 4) kosong, skip
                if (!isset($row[4]) || trim($row[4]) === '') continue;

                // Ambil dan bersihkan data fundamental untuk validasi duplikat
                $noKso = !empty($row[3]) ? trim($row[3]) : '';
                $item  = strtoupper(trim($row[4]));
                $qty = isset($row[6]) ? intval($row[6]) : 0;

                // 🚨 VALIDASI KOMBINASI 3 KOLOM UNIK (nokso, item, qty)
                // Format key di memory menjadi: "KSO123_BAN-01_10"
                $uniqueKey = $noKso . '_' . $item . '_' . $qty;

                if (isset($uniqueKeys[$uniqueKey])) {
                    // Kalau kombinasi nokso, item, dan qty ini sudah ada, skip baris duplikat ini!
                    continue;
                }

                // Daftarkan key ini ke memory agar baris berikutnya yang sama persis langsung ditolak
                $uniqueKeys[$uniqueKey] = true;

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
                    'nokso'              => $noKso !== '' ? $noKso : null,
                    'item'               => $item,
                    'deskripsi'          => !empty($row[5]) ? trim($row[5]) : null,  // ✅ index 5
                    'qty'                => isset($row[6]) ? intval($row[6]) : 0,    // ✅ index 6, hapus trim()
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
                'message' => "Sukses membersihkan data lama & memfilter duplikasi internal Excel. Berhasil menyuntik masal {$insertCount} baris data unik ke Gudang {$warehouse}!",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error SQL local database: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambahkan method ini di dalam class OracleVsFisikAppksoController
    public function getWarehouseList()
    {
        $warehouses = DB::table('so_all_wh_appkso_db')
            ->select('warehouse')
            ->distinct()
            ->whereNotNull('warehouse')
            ->orderBy('warehouse', 'asc')
            ->pluck('warehouse');

        return response()->json([
            'status' => 'success',
            'warehouses' => $warehouses
        ]);
    }
}
