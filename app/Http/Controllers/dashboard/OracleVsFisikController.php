<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikController extends Controller
{
    public function index()
    {
        // 1. AMBIL DATA WAREHOUSE UNTUK DROPDOWN SWEETALERT
        $warehouses = DB::table('so_all_wh_appkso_db')
            ->select('warehouse')
            ->whereNotNull('warehouse')
            ->where('warehouse', '!=', '')
            ->distinct()
            ->pluck('warehouse');

        return view('dashboard.oracle_vs_fisik.oracle_vs_fisik', [
            'warehouses' => $warehouses
        ]);
    }

    public function switchMenu(Request $request)
    {
        $menu = $request->query('menu');

        if ($menu === 'progress') {
            // 2. PERBAIKAN NAMA CONTROLLER PROGRESS
            return app(\App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikProgressSOController::class)
                ->index($request);
        }

        $viewPath = match ($menu) {
            'master_size'        => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_master_size',
            'oracle_snapshot'    => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_snapshot',
            'barcode_monitoring' => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_barcode_monstock',
            'tagstock'           => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_tagstock',
            'tagstock_nonbarcode' => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_tagstoknonbarcode',
            'appkso'             => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_appkso',
            'pic'                => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_pic',
            'default'            => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
            default              => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
        };

        // 3. JIKA MENU DEFAULT, KIRIM DATA WAREHOUSE JUGA
        if ($menu === 'default' || $menu === null) {
            $warehouses = DB::table('so_all_wh_appkso_db')
                ->select('warehouse')
                ->whereNotNull('warehouse')
                ->where('warehouse', '!=', '')
                ->distinct()
                ->pluck('warehouse');

            return view($viewPath, ['warehouses' => $warehouses])->render();
        }

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

        // 1. Buat subquery fisik yang sudah di-sum per item
        $fisikSub = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_fisik'))
            ->groupBy('item');

        // 2. Gunakan $fisikSub tersebut di join utama
        $data = DB::table('so_all_wh_snapshot_db as s')
            ->join('so_all_wh_master_size_db as m', 's.item', '=', 'm.item')
            ->leftJoinSub($fisikSub, 'f', 's.item', '=', 'f.item') // JOIN DENGAN SUBQUERY
            ->where('s.warehouse', $warehouse)
            ->select(
                's.item',
                'm.description',
                'm.grade',
                DB::raw('SUM(s.qty) as qty_oracle'),
                DB::raw('COALESCE(f.total_fisik, 0) as qty_fisik') // Panggil total_fisik dari subquery
            )
            ->groupBy('s.item', 'm.description', 'm.grade', 'f.total_fisik')
            ->havingRaw('qty_fisik < qty_oracle')
            ->get()
            ->map(function ($item) {
                // Sekarang perhitungan sisa di sini pasti akurat
                $sisa = $item->qty_oracle - $item->qty_fisik;
                $progress = ($item->qty_oracle > 0) ? ($item->qty_fisik / $item->qty_oracle) * 100 : 0;

                return [
                    'item' => $item->item,
                    'description' => $item->description,
                    'grade' => $item->grade,
                    'qty_sisa' => $sisa,
                    'persen' => ($sisa > 0)
                        ? min(round($progress, 2), 99.99)
                        : 100
                ];
            })
            ->sortByDesc('persen')
            ->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getDetailPricePattern(Request $request)
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

        // Bawa join tabel harga (p)
        $items = DB::table('so_all_wh_master_size_db as m')
            ->leftJoinSub($oracleSub, 'o', 'm.item', '=', 'o.item')
            ->leftJoinSub($fisikSub, 'f', 'm.item', '=', 'f.item')
            ->leftJoin('so_all_wh_price_db as p', 'm.item', '=', 'p.item')
            ->where('m.pattern', $pattern)
            ->where('m.grade', $grade)
            ->where('m.warehouse', $warehouse)
            ->select(
                'm.item',
                'm.description',
                DB::raw('COALESCE(p.price, 0) as price'),
                DB::raw('COALESCE(o.total_oracle, 0) as oracle_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) as appkso_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0) as variance'),
                // Kalkulasi langsung di database biar kenceng
                DB::raw('(COALESCE(f.total_fisik, 0) * COALESCE(p.price, 0)) as counted_rp'),
                DB::raw('(COALESCE(o.total_oracle, 0) * COALESCE(p.price, 0)) as snapshot_rp'),
                DB::raw('((COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0)) * COALESCE(p.price, 0)) as variance_rp')
            )
            ->get();

        // Pisah data minus dan plus
        $minusData = $items->where('variance', '<', 0)->sortBy('variance')->values();
        $plusData  = $items->where('variance', '>', 0)->sortByDesc('variance')->values();

        // Cari item bermasalah (variance != 0) tapi harganya masih 0 atau NULL
        $missingPriceCount = $items->where('variance', '!=', 0)->where('price', 0)->count();
        $totalSkuDinamis = $minusData->count() + $plusData->count();

        // Panggil view partial khusus harga
        $html = view('dashboard.oracle_vs_fisik.partials.modal_detail_price', [
            'minusData' => $minusData,
            'plusData' => $plusData
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'summary' => [
                'total_sku_dinamis' => $totalSkuDinamis,
                'missing_price_count' => $missingPriceCount,
                'sku_minus' => $minusData->count(),
                'sku_plus'  => $plusData->count(),
                'total_pcs_variance' => $items->sum('variance'),
                'total_rp_variance' => $items->sum('variance_rp'),
            ]
        ]);
    }

    public function getDetailGrade(Request $request)
    {
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

        $query = DB::table('so_all_wh_master_size_db as m')
            ->leftJoinSub($oracleSub, 'o', 'm.item', '=', 'o.item')
            ->leftJoinSub($fisikSub, 'f', 'm.item', '=', 'f.item')
            ->where('m.warehouse', $warehouse)
            ->select(
                'm.pattern',
                'm.item',
                'm.description',
                DB::raw('COALESCE(o.total_oracle, 0) as oracle_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) as appkso_qty'),
                DB::raw('COALESCE(f.total_fisik, 0) - COALESCE(o.total_oracle, 0) as variance')
            );

        // Kalau parameternya spesifik OE atau OK, kita filter.
        // Kalau lu nanti mau bikin buat Card 3 (Mix), kirim aja grade='MIX'
        if ($grade !== 'MIX') {
            $query->where('m.grade', $grade);
        }

        $items = $query->get();

        // Buang yang variance-nya 0 (yang balance gak perlu ditampilin)
        $problematicItems = $items->filter(function ($item) {
            return $item->variance != 0;
        });

        // Pisahkan sorting untuk setiap data
        $minusData = $problematicItems->where('variance', '<', 0)->sortBy('variance')->values();
        $plusData  = $problematicItems->where('variance', '>', 0)->sortByDesc('variance')->values();

        $html = view('dashboard.oracle_vs_fisik.partials.modal_detail_grade', [
            'minusData' => $minusData,
            'plusData' => $plusData
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'summary' => [
                'minus_sku' => $minusData->count(),
                'plus_sku'  => $plusData->count(),
                'total_pcs' => $problematicItems->sum('variance')
            ]
        ]);
    }

    public function getDetailPPM(Request $request)
    {
        $warehouse = $request->query('warehouse');

        if (!$warehouse) {
            return response()->json(['success' => false, 'message' => 'Warehouse tidak ditemukan']);
        }

        // Gunakan subquery yang sudah difilter WAREHOUSE sebelum di-JOIN
        $oracleSub = DB::table('so_all_wh_snapshot_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_oracle'))
            ->groupBy('item');

        $fisikSub = DB::table('so_all_wh_appkso_db')
            ->where('warehouse', $warehouse)
            ->select('item', DB::raw('SUM(qty) as total_fisik'))
            ->groupBy('item');

        // Query utama: Ambil produk HANYA yang ada di master_size gudang ini
        $data = DB::table('so_all_wh_master_size_db as m')
            ->leftJoinSub($oracleSub, 'o', 'm.item', '=', 'o.item')
            ->leftJoinSub($fisikSub, 'f', 'm.item', '=', 'f.item')
            ->where('m.warehouse', $warehouse)
            ->select(
                'm.product',
                'm.grade',
                DB::raw('SUM(COALESCE(o.total_oracle, 0)) as on_hand'),
                DB::raw('SUM(COALESCE(f.total_fisik, 0)) as counted')
            )
            ->groupBy('m.product', 'm.grade')
            ->get();

        return view('dashboard.oracle_vs_fisik.partials.modal_detail_ppm', [
            'data' => $data,
            'warehouse' => $warehouse
        ])->render();
    }
}
