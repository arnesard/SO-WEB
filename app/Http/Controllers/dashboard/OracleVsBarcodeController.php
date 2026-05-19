<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Services\dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsBarcodeController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $filters = $this->dashboardService->getMasterFilters();

        // AMBIL DATA PATTERN DENGAN RAW QUERY AGAR VIRTUAL COLUMN PASTI TERBACA
        $allPatterns = DB::table('master_items')
            ->select(DB::raw('DISTINCT (pattern) as pattern_name'))
            ->whereNotNull('pattern')
            ->whereRaw("pattern != ''")
            ->orderBy('pattern', 'asc')
            ->get()
            ->pluck('pattern_name')
            ->toArray();

        $oracleDates = DB::table('report_daily_transactions_stk_akhir')->whereNotNull('transaction_date')->distinct()->pluck('transaction_date')->toArray();
        $barcodeDates = DB::table('stock_barcodes_resume')->whereNotNull('transaction_date')->distinct()->pluck('transaction_date')->toArray();
        $activeDates = array_unique(array_merge($oracleDates, $barcodeDates));

        return view('dashboard.dashboard', compact('filters', 'oracleDates', 'barcodeDates', 'activeDates', 'allPatterns'));
    }

    public function getChartData(Request $request)
    {
        // Ambil bulan & tahun. Jika request kosong, pakai bulan berjalan
        $month = $request->month ?: date('m');
        $year  = $request->year ?: date('Y');
        $date  = $request->date;

        // 1. Filter Master Items (Scope Data berdasarkan Slicer)
        $queryMaster = DB::table('master_items')->select('item_code_desc');

        if ($request->patterns) {
            if ($request->patterns === 'ALL_OE') {
                // Kalau ALL OE diklik, ambil semua yang depannya OE
                $queryMaster->where('pattern', 'LIKE', 'OE%');
            } elseif ($request->patterns === 'ALL_OK') {
                // Kalau ALL OK diklik, ambil semua yang depannya OK
                $queryMaster->where('pattern', 'LIKE', 'OK%');
            } else {
                // Kalau pattern spesifik diklik
                $queryMaster->where('pattern', $request->patterns);
            }
        }
        // Pastikan filter hanya jalan jika nilainya tidak kosong/ALL
        if ($request->grades) $queryMaster->where('grade', $request->grades);
        if ($request->products) $queryMaster->where('product', $request->products);
        if ($request->types) $queryMaster->where('type', $request->types);
        if ($request->brands) $queryMaster->where('brand', $request->brands);
        if ($request->categories) $queryMaster->where('category', $request->categories);

        $itemCodes = $queryMaster->pluck('item_code_desc')->toArray();

        // Jika filter menghasilkan 0 item, langsung return kosong agar tidak berat di query selanjutnya
        if (empty($itemCodes)) {
            return response()->json(['labels' => [], 'oracle' => [], 'barcode' => [], 'count' => 0]);
        }

        // 2. Ambil Total Oracle per hari
        $oracleData = DB::table('report_daily_transactions_stk_akhir')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereIn('item_code_desc', $itemCodes)
            ->select(DB::raw('DAY(transaction_date) as tgl'), DB::raw('SUM(qty) as total'))
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        // 3. Ambil Total Barcode per hari
        $barcodeData = DB::table('stock_barcodes_resume')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->whereIn('item_code_desc', $itemCodes)
            ->select(DB::raw('DAY(transaction_date) as tgl'), DB::raw('SUM(qty) as total'))
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        // 4. Susun Label 1 sampai Akhir Bulan
        $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
        $labels = [];
        $oracleSeries = [];
        $barcodeSeries = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = "Tgl " . $i;
            $oracleSeries[] = (int)($oracleData[$i] ?? 0);
            $barcodeSeries[] = (int)($barcodeData[$i] ?? 0);
        }

        $summaryQuery = DB::table('master_items as mi')
            ->select(
                'mi.pattern',
                DB::raw('SUM(COALESCE(ora.total, 0)) as oracle_total'),
                DB::raw('SUM(COALESCE(bar.total, 0)) as barcode_total'),
                DB::raw('COUNT(CASE WHEN COALESCE(bar.total, 0) < COALESCE(ora.total, 0) THEN 1 END) as sku_minus'),
                DB::raw('COUNT(CASE WHEN COALESCE(bar.total, 0) > COALESCE(ora.total, 0) THEN 1 END) as sku_plus')
            )
            // Join ke subquery Oracle
            ->leftJoin(DB::raw("(SELECT item_code_desc, SUM(qty) as total FROM report_daily_transactions_stk_akhir
            WHERE " . ($date ? "transaction_date = '$date'" : "MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year") . "
            GROUP BY item_code_desc) as ora"), 'mi.item_code_desc', '=', 'ora.item_code_desc')
            // Join ke subquery Barcode
            ->leftJoin(DB::raw("(SELECT item_code_desc, SUM(qty) as total FROM stock_barcodes_resume
            WHERE " . ($date ? "transaction_date = '$date'" : "MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year") . "
            GROUP BY item_code_desc) as bar"), 'mi.item_code_desc', '=', 'bar.item_code_desc')
            ->groupBy('mi.pattern')
            ->get();

        $patternsOE = $summaryQuery->filter(fn($item) => str_starts_with($item->pattern, 'OE'))->pluck('pattern')->unique()->values();
        $patternsOK = $summaryQuery->filter(fn($item) => str_starts_with($item->pattern, 'OK'))->pluck('pattern')->unique()->values();


        return response()->json([
            'labels' => $labels,
            'oracle' => $oracleSeries,
            'barcode' => $barcodeSeries,
            'summary_table' => $summaryQuery,
            'patterns_oe' => $patternsOE,
            'patterns_ok' => $patternsOK,
            'month_name' => date('F', mktime(0, 0, 0, (int)$month, 10)),
            'year' => $year,
            'count' => count($itemCodes)
        ]);
    }

    public function getDetailByPattern(Request $request)
    {
        $pattern = $request->pattern;
        $date    = $request->date;
        $month   = $request->month ?: date('m');
        $year    = $request->year ?: date('Y');

        // Ambil detail breakdown per item_code_desc
        $details = DB::table('master_items as mi')
            ->where('mi.pattern', $pattern)
            ->select(
                'mi.item_code_desc',
                'mi.description',
                DB::raw('SUM(COALESCE(ora.total, 0)) as oracle_total'),
                DB::raw('SUM(COALESCE(bar.total, 0)) as barcode_total')
            )
            // Join ke subquery Oracle
            ->leftJoin(DB::raw("(SELECT item_code_desc, SUM(qty) as total FROM report_daily_transactions_stk_akhir
            WHERE " . ($date ? "transaction_date = '$date'" : "MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year") . "
            GROUP BY item_code_desc) as ora"), 'mi.item_code_desc', '=', 'ora.item_code_desc')
            // Join ke subquery Barcode
            ->leftJoin(DB::raw("(SELECT item_code_desc, SUM(qty) as total FROM stock_barcodes_resume
            WHERE " . ($date ? "transaction_date = '$date'" : "MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year") . "
            GROUP BY item_code_desc) as bar"), 'mi.item_code_desc', '=', 'bar.item_code_desc')
            ->groupBy('mi.item_code_desc', 'mi.description')
            ->get();

        return response()->json([
            'pattern' => $pattern,
            'date'    => $date,
            'details' => $details
        ]);
    }

    public function getDeepDetailBarcode(Request $request)
    {
        $itemCodeDesc = $request->item_code_desc;
        $date = $request->date;
        $month = $request->month;
        $year = $request->year;

        $query = DB::table('stock_barcodes_resume')
            ->where('item_code_desc', $itemCodeDesc);

        if ($date) {
            $query->where('transaction_date', $date);
        } else {
            $query->whereMonth('transaction_date', $month)->whereYear('transaction_date', $year);
        }

        $data = $query->select('loc_code', 'rack_code', 'item_code_desc', 'description', 'qty')->get();

        return response()->json($data);
    }
}
