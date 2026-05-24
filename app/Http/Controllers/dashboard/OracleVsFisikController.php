<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikController extends Controller
{
    public function index()
    {
        return view('dashboard.oracle_vs_fisik.oracle_vs_fisik');
    }

    public function switchMenu(Request $request)
    {
        $menu = $request->query('menu');

        if ($menu === 'progress') {
            return app(\App\Http\Controllers\dashboard\ProgressController::class)
                ->index();
        }

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

    public function getComparisonData(Request $request)
    {
        $warehouse = $request->query('warehouse');

        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Warehouse is required']);
        }

        $oracleSub = DB::table('so_all_wh_snapshot_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_oracle'))
            ->groupBy('item');

        $fisikSub = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_fisik'))
            ->groupBy('item');

        $data = DB::table('so_all_wh_master_size_db as m')
            ->leftJoinSub($oracleSub, 'o', function ($join) {
                $join->on('m.item', '=', 'o.item');
            })
            ->leftJoinSub($fisikSub, 'f', function ($join) {
                $join->on('m.item', '=', 'f.item');
            })
            ->leftJoin('so_all_wh_price_db as p', 'm.item', '=', 'p.item')
            ->where('m.warehouse', $warehouse)
            ->select(
                'm.pattern',
                'm.grade',
                DB::raw('SUM(COALESCE(o.total_oracle, 0)) as qty_oracle'),
                DB::raw('SUM(COALESCE(f.total_fisik, 0)) as qty_appkso'),
                DB::raw('SUM(COALESCE(f.total_fisik, 0)) - SUM(COALESCE(o.total_oracle, 0)) as variance'),
                DB::raw('SUM(ABS(COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0))) as gross_variance'),
                DB::raw('SUM(CASE WHEN COALESCE(f.total_fisik, 0) < COALESCE(o.total_oracle, 0) THEN 1 ELSE 0 END) as sku_minus'),
                DB::raw('SUM(CASE WHEN COALESCE(f.total_fisik, 0) > COALESCE(o.total_oracle, 0) THEN 1 ELSE 0 END) as sku_plus'),
                DB::raw('SUM(CASE WHEN COALESCE(o.total_oracle, 0) > 0 AND COALESCE(f.total_fisik, 0) = 0 THEN 1 ELSE 0 END) as unscanned_sku'),
                DB::raw('SUM( (COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0)) * COALESCE(p.price, 0) ) as price_variance')
            )
            ->groupBy('m.pattern', 'm.grade')
            ->get();

        // HITUNG PERSENTASE SKU (ITEM DISTINCT / ITEM SAMA DIHITUNG 1)
        $totalItemOracle = DB::table('so_all_wh_snapshot_db')
            ->where('warehouse', $warehouse)
            ->count(DB::raw('DISTINCT item'));

        $totalItemAppkso = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->count(DB::raw('DISTINCT item'));

        $skuPercentage   = $totalItemOracle > 0 ? round(($totalItemAppkso / $totalItemOracle) * 100, 2) : 0;


        $totalOracle   = (int) $data->sum('qty_oracle');
        $totalAppkso   = (int) $data->sum('qty_appkso');
        $grossVariance = $data->sum('gross_variance');
        $accuracyRate  = $totalOracle > 0 ? round((1 - ($grossVariance / $totalOracle)) * 100, 2) : 0;

        // =========================================================================
        // DATA KHUSUS GRADE OE (Diubah ke .sum('variance') agar sinkron)
        // =========================================================================
        $dataOE        = $data->where('grade', 'OE');
        $variancePcsOE = (int) $dataOE->sum('variance');
        $skuMinusOE    = (int) $dataOE->sum('sku_minus');
        $skuPlusOE     = (int) $dataOE->sum('sku_plus');
        $priceVarianceOE = (float) $dataOE->sum('price_variance');

        // =========================================================================
        // DATA KHUSUS GRADE OK (Diubah ke .sum('variance') agar sinkron)
        // =========================================================================
        $dataOK        = $data->where('grade', 'OK');
        $variancePcsOK = (int) $dataOK->sum('variance');
        $skuMinusOK    = (int) $dataOK->sum('sku_minus');
        $skuPlusOK     = (int) $dataOK->sum('sku_plus');
        $priceVarianceOK = (float) $dataOK->sum('price_variance');

        $totalSelisihPcs = abs($variancePcsOE) + abs($variancePcsOK);
        $variancePPM = $totalOracle > 0 ? round(($totalSelisihPcs / $totalOracle) * 1000000, 0) : 0;


        $summary = [
            'total_oracle'    => $totalOracle,
            'total_appkso'    => $totalAppkso,
            'total_minus'     => (int) $data->sum('sku_minus'),
            'total_plus'      => (int) $data->sum('sku_plus'),
            'net_variance'    => (int) $data->sum('variance'), // <--- Ini acuan total variance global
            'gross_variance'  => (int) $grossVariance,
            'unscanned_sku'   => (int) $data->sum('unscanned_sku'),
            'accuracy_rate'   => $accuracyRate,
            'oe_variance_pcs' => $variancePcsOE,
            'oe_sku_minus'    => $skuMinusOE,
            'oe_sku_plus'     => $skuPlusOE,
            'oe_price_variance' => $priceVarianceOE,
            'ok_variance_pcs' => $variancePcsOK,
            'ok_sku_minus'    => $skuMinusOK,
            'ok_sku_plus'     => $skuPlusOK,
            'ok_price_variance' => $priceVarianceOK,
            'variance_ppm'    => (int) $variancePPM,
            'total_price_variance' => (float) $data->sum('price_variance'),
            'sku_percentage'       => $skuPercentage,
            'total_item_oracle'    => $totalItemOracle,
            'total_item_appkso'    => $totalItemAppkso,
        ];

        return response()->json([
            'success' => true,
            'data'    => $data,
            'summary' => $summary
        ]);
    }

    public function getDetailPattern(Request $request)
    {
        $pattern = $request->query('pattern');
        $grade = $request->query('grade');
        $warehouse = $request->query('warehouse');

        $oracleSub = DB::table('so_all_wh_snapshot_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_oracle'))
            ->groupBy('item');

        $fisikSub = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_fisik'))
            ->groupBy('item');

        $items = DB::table('so_all_wh_master_size_db as m')
            ->leftJoinSub($oracleSub, 'o', 'm.item', '=', 'o.item')
            ->leftJoinSub($fisikSub, 'f', 'm.item', '=', 'f.item')
            ->where('m.pattern', $pattern)
            ->where('m.grade', $grade)
            ->where('m.warehouse', $warehouse)
            ->select(
                'm.item',
                'm.description',
                DB::raw('COALESCE(o.total_oracle, 0) as oracle_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) as appkso_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0) as variance')
            )
            ->get();

        $sortedItems = $items->sortBy('variance')->values();

        // Pisahkan sorting untuk setiap data
        $minusData = $items->where('variance', '<', 0)->sortBy('variance')->values(); // Minus terbesar (misal -100 ke -1)
        $plusData  = $items->where('variance', '>', 0)->sortByDesc('variance')->values(); // Plus terbesar (misal 100 ke 1)

        $html = view('dashboard.oracle_vs_fisik.partials.modal_detail', [
            'minusData' => $minusData,
            'plusData' => $plusData
        ])->render();

        // INI YANG SEBELUMNYA HILANG, BIKIN JS LU ERROR
        return response()->json([
            'html' => $html,
            'summary' => [
                'total_sku' => $items->count(),
                'minus_sku' => $minusData->count(),
                'plus_sku'  => $plusData->count()
            ]
        ]);
    }

    public function getScanHistory(Request $request)
    {
        $item = $request->query('item');
        $warehouse = $request->query('warehouse');

        if (!$item || !$warehouse) {
            return response()->json(['success' => false, 'message' => 'Parameter tidak lengkap']);
        }

        $data = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->where('item', $item)
            ->select('opr', 'oprname', 'nokso', 'item', 'deskripsi', 'qty')
            ->orderBy('id', 'asc') // Urut berdasarkan urutan scan
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'total_qty' => $data->sum('qty')
        ]);
    }

    public function getUnscannedItems(Request $request)
    {
        $warehouse = $request->query('warehouse');
        if (!$warehouse) return response()->json(['success' => false]);

        $data = DB::table('so_all_wh_snapshot_db as s')
            ->join('so_all_wh_master_size_db as m', 's.item', '=', 'm.item')
            ->leftJoin('so_all_wh_appkso_db as f', function ($join) use ($warehouse) {
                $join->on('s.item', '=', 'f.item')->where('f.warehouse', $warehouse);
            })
            ->where('s.warehouse', $warehouse)
            ->select(
                's.item',
                'm.description',
                'm.grade',
                DB::raw('SUM(s.qty) as qty_oracle'),
                DB::raw('COALESCE(SUM(f.qty), 0) as qty_fisik')
            )
            ->groupBy('s.item', 'm.description', 'm.grade')
            // Filter: Hanya tampilkan yang qty_fisik < qty_oracle (belum 100%)
            ->havingRaw('qty_fisik < qty_oracle')
            ->get()
            ->map(function ($item) {
                $progress = ($item->qty_oracle > 0) ? ($item->qty_fisik / $item->qty_oracle) * 100 : 0;
                return [
                    'item' => $item->item,
                    'description' => $item->description,
                    'grade' => $item->grade,
                    'qty_sisa' => $item->qty_oracle - $item->qty_fisik,
                    'persen' => round($progress, 1)
                ];
            })
            ->sortByDesc('persen')
            ->values();

        return response()->json(['success' => true, 'data' => $data]);
    }
}
