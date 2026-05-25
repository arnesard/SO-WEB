<?php

namespace App\Http\Controllers\data_master;

use App\Http\Controllers\Controller;
use App\Services\data_master\ReportDailyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportDailyController extends Controller
{
    protected $reportService;

    public function __construct(ReportDailyService $service)
    {
        $this->reportService = $service;
    }

    public function index()
    {
        $list_kso = DB::connection('mysql_second')->table('ms_kso')
            ->select('so_name', 'def_counter')->orderBy('recid', 'desc')->get();
        return view('data_master.report_daily', compact('list_kso'));
    }

    public function getCalendarStatus(Request $request)
    {
        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
        $dates = DB::table('report_daily_transactions')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->distinct()->pluck('transaction_date')->toArray();
        return response()->json($dates);
    }

    public function getDailyData(Request $request)
    {
        $data = DB::table('report_daily_transactions')
            ->where('transaction_date', $request->date)
            ->orderBy('item_code', 'asc')->get();
        return response()->json($data);
    }

    public function uploadDaily(Request $request)
    {
        if (!$request->hasFile('file_txt')) {
            return response()->json(['success' => false, 'message' => 'File tidak ditemukan'], 400);
        }

        try {
            $count = $this->reportService->processUpload($request->file('file_txt'));
            if ($count === 0) return response()->json(['success' => false, 'message' => 'Data tidak ditemukan dalam file'], 400);
            return response()->json(['success' => true, 'count' => $count]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Eror: ' . $e->getMessage()], 500);
        }
    }

    public function getSummaryData(Request $request)
    {
        $summary = DB::table('report_daily_transactions')
            ->select(
                'gt_type',
                DB::raw('SUM(oe_stk_awal) as oe_stk_awal'),
                DB::raw('SUM(oe_in) as oe_in'),
                DB::raw('SUM(oe_out) as oe_out'),
                DB::raw('SUM(oe_adj) as oe_adj'),
                DB::raw('SUM(oe_stk_akhir) as oe_stk_akhir'),
                DB::raw('SUM(ok_stk_awal) as ok_stk_awal'),
                DB::raw('SUM(ok_in) as ok_in'),
                DB::raw('SUM(ok_out) as ok_out'),
                DB::raw('SUM(ok_adj) as ok_adj'),
                DB::raw('SUM(ok_stk_akhir) as ok_stk_akhir')
            )
            ->where('transaction_date', $request->date)
            ->groupBy('gt_type')->orderBy('gt_type', 'asc')->get();
        return response()->json($summary);
    }

    public function checkMissingMaster(Request $request)
    {
        $date = $request->date;

        // Sekarang join biasa saja sudah pasti jalan karena DB sudah sinkron
        $missingItems = DB::table('report_daily_transactions_stk_akhir as resume')
            ->leftJoin('master_items as ms', 'resume.item_code_desc', '=', 'ms.item_code_desc')
            ->where('resume.transaction_date', $date)
            ->whereNull('ms.item_code_desc') // Ambil yang tidak ada di master
            ->select('resume.item_code_desc', 'resume.description')
            ->distinct()
            ->get();

        return response()->json($missingItems);
    }
}
