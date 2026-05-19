<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Services\dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Entry point untuk Dashboard Oracle vs Fisik
     */
    public function index(Request $request)
    {
        // Ambil filter master (Grade, Product, etc)
        $filters = $this->dashboardService->getMasterFilters();

        // Ambil data pattern unik dari master_items
        $allPatterns = DB::table('master_items')
            ->select(DB::raw('DISTINCT (pattern) as pattern_name'))
            ->whereNotNull('pattern')
            ->whereRaw("pattern != ''")
            ->orderBy('pattern', 'asc')
            ->get()
            ->pluck('pattern_name')
            ->toArray();

        // Ambil tanggal transaksi yang tersedia di report daily (Oracle)
        $oracleDates = DB::table('report_daily_transactions_stk_akhir')
            ->whereNotNull('transaction_date')
            ->distinct()
            ->pluck('transaction_date')
            ->toArray();

        // Kirim data ke view (AJAX Partial)
        if ($request->ajax()) {
            return view('dashboard.oracle_vs_fisik.oracle_vs_fisik', compact('filters', 'allPatterns', 'oracleDates'));
        }

        // Jika diakses langsung via URL (fallback)
        return view('dashboard.dashboard', compact('filters', 'allPatterns', 'oracleDates'));
    }

    /**
     * Endpoint untuk data grafik/summary perbandingan fisik
     */
    public function getFisikData(Request $request)
    {
        // Placeholder untuk logic pengambilan data Aktual Fisik nantinya
        // Lu tinggal tembak tabel yang nyimpen hasil Stock Opname di sini
        return response()->json([
            'success' => true,
            'message' => 'Data fisik siap diolah bro!'
        ]);
    }

    public function switchMenu(Request $request)
    {
        $menu = $request->query('menu');

        $viewPath = match ($menu) {
            'master_size'        => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_master_size',
            'oracle_snapshot'    => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_oracle_snapshot',
            'barcode_monitoring' => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_barcode_monstock',
            'appkso'             => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_appkso',
            default              => 'dashboard.oracle_vs_fisik.oracle_vs_fisik',
        };

        return view($viewPath)->render();
    }
}
