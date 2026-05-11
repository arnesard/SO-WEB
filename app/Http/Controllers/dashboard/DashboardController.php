<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Services\dashboard\DashboardService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $filters = $this->dashboardService->getMasterFilters();

        $oracleDates = DB::table('report_daily_transactions_stk_akhir')->distinct()->pluck('transaction_date')->toArray();
        $barcodeDates = DB::table('stock_barcodes_resume')->distinct()->pluck('transaction_date')->toArray();
        $activeDates = array_unique(array_merge($oracleDates, $barcodeDates));

        return view('dashboard.dashboard', compact('filters', 'oracleDates', 'barcodeDates', 'activeDates'));
    }
}
