<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Pastikan ini ada
use Illuminate\Support\Facades\Log; // Pastikan ini ada

class OracleVsFisikController extends Controller
{
    public function index()
    {
        return view('dashboard.oracle_vs_fisik.oracle_vs_fisik');
    }

    public function switchMenu(Request $request)
    {
        $menu = $request->query('menu');

        $viewPath = match ($menu) {
            'master_size'        => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_master_size',
            'oracle_snapshot'    => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_snapshot',
            'barcode_monitoring' => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_barcode_monstock',
            'tagstock'           => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_tagstock',
            'appkso'             => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_appkso',
            'pic'                => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_pic',
            'default'            => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
            default              => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
        };

        return view($viewPath)->render();
    }

    // 🎯 FUNGSI INI YANG DICARI SAMA ROUTE TAPI SEBELUMNYA GAK ADA DI FILE INI 🎯
    public function getComparisonData(Request $request)
    {
        $warehouse = $request->query('warehouse');

        if (empty($warehouse)) {
            return response()->json(['success' => false, 'message' => 'Gudang tidak ditemukan'], 400);
        }

        try {
            $data = DB::table('so_all_wh_master_size_db as m')
                ->leftJoin('so_all_wh_snapshot_db as s', function ($join) use ($warehouse) {
                    $join->on('m.item', '=', 's.item')
                        ->where('s.warehouse', '=', $warehouse);
                })
                ->leftJoin('so_all_wh_appkso_db as a', function ($join) use ($warehouse) {
                    $join->on('m.item', '=', 'a.item')
                        ->where('a.warehouse', '=', $warehouse);
                })
                ->where('m.warehouse', $warehouse)
                ->select(
                    'm.pattern',
                    'm.grade',
                    DB::raw('SUM(IFNULL(s.qty, 0)) as qty_oracle'),
                    DB::raw('SUM(IFNULL(a.qty, 0)) as qty_appkso'),
                    DB::raw('SUM(IFNULL(a.qty, 0)) - SUM(IFNULL(s.qty, 0)) as variance')
                )
                ->groupBy('m.pattern', 'm.grade')
                ->get();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error("Error saat getComparisonData: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
