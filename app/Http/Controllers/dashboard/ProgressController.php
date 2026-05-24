<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function index()
    {

        /*
        |--------------------------------------------------------------------------
        | SUMMARY GLOBAL
        |--------------------------------------------------------------------------
        */

        $summary = DB::table('so_all_wh_appkso_db')
            ->selectRaw('
                COUNT(*) as total_rows,

                COUNT(
                    CASE
                        WHEN verifikasi_nama IS NOT NULL
                        THEN 1
                    END
                ) as verified_rows,

                SUM(qty) as total_qty,

                SUM(
                    CASE
                        WHEN verifikasi_nama IS NOT NULL
                        THEN qty
                        ELSE 0
                    END
                ) as verified_qty
            ')
            ->first();

        $verifiedRows = $summary->verified_rows ?? 0;
        $totalRows = $summary->total_rows ?? 0;
        $verifiedQty = $summary->verified_qty ?? 0;
        $totalQty = $summary->total_qty ?? 0;

        /*
        |--------------------------------------------------------------------------
        | PROGRESS PER OPERATOR
        |--------------------------------------------------------------------------
        */

        $operators = DB::table('so_all_wh_appkso_db')
            ->select(
                'warehouse',
                'opr',
                'oprname',

                DB::raw('COUNT(DISTINCT nokso) as total_kso'),

                DB::raw('COUNT(
                DISTINCT CASE
                WHEN verifikasi_nama IS NOT NULL
                THEN nokso
                END
            ) as verified_kso'),

                DB::raw('SUM(qty) as total_qty'),

                DB::raw('SUM(
                CASE
                WHEN verifikasi_nama IS NOT NULL
                THEN qty
                ELSE 0
                END
            ) as verified_qty'),

                // ✅ FIX: tambahin koma di atas, lalu baru lokasi
                DB::raw("
                CASE
                    WHEN LEFT(warehouse,2) IN ('G1','G4') THEN 'BPW1'
                    WHEN LEFT(warehouse,2) = 'G2' THEN 'BPW2'
                    WHEN LEFT(warehouse,2) = 'G3' THEN 'BPW3'
                    ELSE 'KARANTINA'
                    END as lokasi
                ")
            )

            ->groupBy(
                'warehouse',
                'opr',
                'oprname',
                DB::raw("
                    CASE
                        WHEN LEFT(warehouse,2) IN ('G1','G4') THEN 'BPW1'
                        WHEN LEFT(warehouse,2) = 'G2' THEN 'BPW2'
                        WHEN LEFT(warehouse,2) = 'G3' THEN 'BPW3'
                        ELSE 'KARANTINA'
                    END
                ")
            )
            ->get()

            ->map(function ($row) {

                $progress = $row->total_qty > 0
                    ? ($row->verified_qty / $row->total_qty) * 100
                    : 0;

                return [
                    'warehouse'      => $row->warehouse,
                    'opr'            => $row->opr,
                    'oprname'        => $row->oprname,

                    'total_kso'      => $row->total_kso,
                    'verified_kso'   => $row->verified_kso,

                    'total_qty'      => $row->total_qty,
                    'verified_qty'   => $row->verified_qty,

                    'progress'       => round($progress, 2),

                    'status' => $progress >= 100
                        ? 'Completed'
                        : 'On Progress'
                ];
            })

            ->sortByDesc('progress')

            ->values();
        /*
        |--------------------------------------------------------------------------
        | SUMMARY PER WAREHOUSE
        |--------------------------------------------------------------------------
        */
        $ksoPerLokasi = DB::table('so_all_wh_appkso_db')
            ->selectRaw("
        CASE
            WHEN LEFT(warehouse,2) IN ('G1','G4') THEN 'BPW1'
            WHEN LEFT(warehouse,2) = 'G2' THEN 'BPW2'
            WHEN LEFT(warehouse,2) = 'G3' THEN 'BPW3'
            ELSE 'KARANTINA'
        END as lokasi,
        COUNT(DISTINCT nokso) as total,
        COUNT(DISTINCT CASE WHEN verifikasi_nama IS NOT NULL THEN nokso END) as verifikasi
    ")
            ->groupBy('lokasi')
            ->get()
            ->keyBy('lokasi');


        $warehouseSummary = DB::table('so_all_wh_appkso_db')
            ->select(
                'warehouse',

                DB::raw('COUNT(DISTINCT nokso) as total_kso'),

                DB::raw('COUNT(
                    DISTINCT CASE
                    WHEN verifikasi_nama IS NOT NULL
                    THEN nokso
                    END
                ) as verified_kso')
            )

            ->groupBy('warehouse')

            ->get();

        $auditorsData = DB::table('so_all_wh_pic_auditor_db as a')

            ->leftJoin('so_all_wh_appkso_db as d', function ($join) {

                $join->on('a.warehouse', '=', 'd.warehouse');
            })
            ->leftJoin('so_all_wh_pic_stock_db as s', function ($join) {

                $join->on('a.warehouse', '=', 's.warehouse')
                    ->on('a.gedung', '=', 's.gedung')
                    ->on('a.lot', '=', 's.lot');
            })
            ->select(

                'a.nama as auditor',
                's.nama as auditee',
                'a.gedung as lokasi',

                DB::raw('COUNT(DISTINCT a.lot) as kso_total'),

                DB::raw('COUNT(
                DISTINCT CASE
                WHEN d.verifikasi_nama IS NOT NULL
                THEN d.nokso
                END
            ) as kso_verifikasi'),

                DB::raw('SUM(d.qty) as total_product'),

                DB::raw('SUM(
                CASE
                WHEN d.verifikasi_nama IS NOT NULL
                THEN d.qty
                ELSE 0
                END
            ) as verified_product')

            )

            ->groupBy(
                'a.nama',
                's.nama',
                'a.gedung'
            )

            ->get()

            ->map(function ($row) {

                $progress = $row->total_product > 0
                    ? ($row->verified_product / $row->total_product) * 100
                    : 0;

                return [

                    'auditor' => $row->auditor,
                    'auditee' => $row->auditee,

                    'lokasi' => $row->lokasi,

                    'kso_total' => $row->kso_total,

                    'kso_verifikasi' => $row->kso_verifikasi,

                    'total_product' => $row->total_product ?? 0,

                    'verified_product' => $row->verified_product ?? 0,

                    'progres' => round($progress, 2),

                    'status' => $progress >= 100
                        ? '✔ Completed'
                        : '⏳ On Progress'
                ];
            });

        $locationSummary = DB::table('so_all_wh_appkso_db')
            ->selectRaw("
        CASE
            WHEN LEFT(warehouse,2) IN ('G1','G4') THEN 'BPW1'
            WHEN LEFT(warehouse,2) = 'G2' THEN 'BPW2'
            WHEN LEFT(warehouse,2) = 'G3' THEN 'BPW3'
            ELSE 'KARANTINA'
        END as lokasi,
        SUM(qty) as total_qty,
        SUM(CASE WHEN verifikasi_nama IS NOT NULL THEN qty ELSE 0 END) as verified_qty
    ")
            ->groupBy('lokasi')
            ->get()
            ->keyBy('lokasi');


        return view('dashboard.progress', [
            'summary' => $summary,

            'operators' => $operators,

            'warehouseSummary' => $warehouseSummary,

            'auditorsData' => $auditorsData,

            'ksoPerLokasi' => $ksoPerLokasi,

            'verifiedRows' => $verifiedRows,
            'totalRows' => $totalRows,
            'verifiedQty' => $verifiedQty,
            'totalQty' => $totalQty,
            'locationSummary' => $locationSummary,
        ]);
    }
}
