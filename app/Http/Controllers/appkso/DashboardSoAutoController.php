<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardSoAutoController extends Controller
{
    public function index()
    {
        $list_kso = DB::connection('mysql_second')
            ->table('ms_kso')
            ->select('so_name')
            ->orderBy('recid', 'desc')
            ->get();

        return view('appkso.dashboard_so_auto', compact('list_kso'));
    }

    public function getComparisonData(Request $request)
    {
        $so_name = $request->query('so_name');

        if (!isset($summary['resume_data'])) {
            $summary['resume_data'] = [
                'OE TIRE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                'OE TUBE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                'OK TIRE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                'OK TUBE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
            ];
        }

        if (empty($so_name)) return response()->json(['success' => false], 400);

        try {
            $cntso_grouped = DB::connection('mysql_second')->table('cntso')
                ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty_appkso'))
                ->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');

            $snapshot_grouped = DB::table('snapshot_bpw')
                ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty_oracle'))
                ->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');

            $master_map = DB::table('so_all_wh_master_size_db')
                ->select(DB::raw('TRIM(item) as item'), 'pattern', 'grade', 'description', 'product')->get()->keyBy('item');

            $price_map = DB::table('so_all_wh_price_db')
                ->select(DB::raw('TRIM(item) as item'), 'price')->get()->keyBy('item');

            $all_items = collect($cntso_grouped->keys())->merge($snapshot_grouped->keys())->unique();

            $results = [];
            $summary = [
                'total_item_oracle' => 0,
                'total_item_appkso' => 0,
                'total_oracle' => 0,
                'total_appkso' => 0,
                'net_variance' => 0,
                'gross_variance' => 0,
                'total_minus' => 0,
                'total_plus' => 0,
                'unscanned_sku' => 0,
                'minus_sku' => 0,
                'plus_sku' => 0,
                'oe_variance_pcs' => 0,
                'oe_sku_minus' => 0,
                'oe_sku_plus' => 0,
                'ok_variance_pcs' => 0,
                'ok_sku_minus' => 0,
                'ok_sku_plus' => 0,
                'total_price_variance' => 0,
                'oe_price_variance' => 0,
                'ok_price_variance' => 0,
                'missing_price_count' => 0,
                'variance_ppm' => 0,
                'accuracy_rate' => 0,
                'resume_data' => [
                    'OE TIRE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                    'OK TUBE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                    'OK TIRE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                    'OE TUBE' => ['counted' => 0, 'on_hand' => 0, 'variance' => 0],
                ]
            ];

            $ppm_group = [];

            foreach ($all_items as $itemCode) {
                $qty_fisik  = isset($cntso_grouped[$itemCode]) ? (int) $cntso_grouped[$itemCode]->qty_appkso : 0;
                $qty_oracle = isset($snapshot_grouped[$itemCode]) ? (int) $snapshot_grouped[$itemCode]->qty_oracle : 0;
                $variance = $qty_fisik - $qty_oracle;

                $master  = $master_map->get($itemCode);

                // --- PENGAMANAN ANTI ERROR 500 (CEK NULL) ---
                $pattern = ($master && $master->pattern) ? trim($master->pattern) : 'UNIDENTIFIED';
                $grade   = ($master && $master->grade) ? strtoupper(trim($master->grade)) : 'UNKNOWN';
                $product = ($master && $master->product) ? strtoupper(trim($master->product)) : 'LAINNYA';

                // Jika isinya cuma spasi kosong, kembalikan ke default
                if ($pattern === '') $pattern = 'UNIDENTIFIED';
                if ($grade === '') $grade = 'UNKNOWN';
                if ($product === '') $product = 'LAINNYA';
                // ---------------------------------------------

                $price_obj = $price_map->get($itemCode);
                $price     = $price_obj ? (int) $price_obj->price : 0;

                $grade_clean = in_array($grade, ['OE', 'OK']) ? $grade : '';
                $product_clean = in_array($product, ['TIRE', 'TUBE']) ? $product : '';

                if ($grade_clean && $product_clean) {
                    $resume_key = $grade_clean . ' ' . $product_clean;
                    if (isset($summary['resume_data'][$resume_key])) {
                        $summary['resume_data'][$resume_key]['counted'] += $qty_fisik;
                        $summary['resume_data'][$resume_key]['on_hand'] += $qty_oracle;
                        $summary['resume_data'][$resume_key]['variance'] += $variance;
                    }
                }

                // --- LOGIKA UNTUK MENYAMAKAN HITUNGAN PPM DENGAN MODAL ---
                $grade_ppm = in_array($grade, ['OE', 'OK', '2ND']) ? $grade : '2ND';
                $ppm_key = $product . '|' . $grade_ppm;

                if (!isset($ppm_group[$ppm_key])) {
                    $ppm_group[$ppm_key] = 0;
                }
                $ppm_group[$ppm_key] += $variance; // Akumulasi selisih netto per grup
                // -----------------------------------------------------------

                if ($price === 0 && $variance !== 0) $summary['missing_price_count']++;
                $price_variance = $variance * $price;

                $key = $pattern . '|' . $grade;
                if (!isset($results[$key])) {
                    $results[$key] = [
                        'pattern' => $pattern,
                        'grade' => $grade,
                        'qty_oracle' => 0,
                        'qty_appkso' => 0,
                        'variance' => 0,
                        'oracle_rp' => 0,
                        'appkso_rp' => 0,
                        'variance_rp' => 0,
                        'sku_minus' => 0,
                        'sku_plus' => 0,
                    ];
                }

                $results[$key]['qty_oracle'] += $qty_oracle;
                $results[$key]['qty_appkso'] += $qty_fisik;
                $results[$key]['variance']   += $variance;

                $results[$key]['oracle_rp'] += $qty_oracle * $price;
                $results[$key]['appkso_rp'] += $qty_fisik * $price;
                $results[$key]['variance_rp'] += $variance * $price;

                if ($variance < 0) $results[$key]['sku_minus']++;
                elseif ($variance > 0) $results[$key]['sku_plus']++;

                if ($qty_oracle > 0) $summary['total_item_oracle']++;
                if ($qty_fisik > 0)  $summary['total_item_appkso']++;
                if ($qty_oracle > 0 && $qty_fisik == 0) $summary['unscanned_sku']++;

                $summary['total_oracle'] += $qty_oracle;
                $summary['total_appkso'] += $qty_fisik;
                $summary['total_price_variance'] += $price_variance;

                if ($variance < 0) {
                    $summary['total_minus'] += abs($variance);
                    $summary['minus_sku']++;
                    if ($grade === 'OE') $summary['oe_sku_minus']++;
                    else $summary['ok_sku_minus']++;
                } elseif ($variance > 0) {
                    $summary['total_plus'] += $variance;
                    $summary['plus_sku']++;
                    if ($grade === 'OE') $summary['oe_sku_plus']++;
                    else $summary['ok_sku_plus']++;
                }

                if ($grade === 'OE') {
                    $summary['oe_variance_pcs'] += $variance;
                    $summary['oe_price_variance'] += $price_variance;
                } else {
                    $summary['ok_variance_pcs'] += $variance;
                    $summary['ok_price_variance'] += $price_variance;
                }
            }

            // --- HITUNG TOTAL ABSOLUT VARIANCE DARI GRUP PPM ---
            $total_absolute_variance_ppm = 0;
            foreach ($ppm_group as $val) {
                $total_absolute_variance_ppm += abs($val);
            }

            $summary['net_variance']   = $summary['total_appkso'] - $summary['total_oracle'];
            $summary['gross_variance'] = $summary['total_plus'] + $summary['total_minus'];
            $summary['sku_percentage'] = $summary['total_item_oracle'] > 0 ? round(($summary['total_item_appkso'] / $summary['total_item_oracle']) * 100, 2) : 0;
            $summary['accuracy_rate'] = $summary['total_oracle'] > 0 ? round((1 - ($summary['gross_variance'] / $summary['total_oracle'])) * 100, 2) : 0;
            $summary['variance_ppm'] = $summary['total_oracle'] > 0 ? round(($total_absolute_variance_ppm / $summary['total_oracle']) * 1000000, 2) : 0;

            // Pastikan baris ini ada untuk kalkulasi progress
            $summary['total_oracle'] = $snapshot_grouped->sum('qty_oracle');
            $summary['total_appkso'] = $cntso_grouped->sum('qty_appkso');
            $summary['progress_scan'] = ($summary['total_oracle'] > 0)
                ? round(($summary['total_appkso'] / $summary['total_oracle']) * 100, 2)
                : 0;

            $oracle_oe = $oracle_ok = $appkso_oe = $appkso_ok = 0;
            foreach ($all_items as $itemCode) {
                $master = $master_map->get($itemCode);
                $grade  = ($master && $master->grade) ? strtoupper(trim($master->grade)) : 'UNKNOWN';
                $qo     = isset($snapshot_grouped[$itemCode]) ? (int) $snapshot_grouped[$itemCode]->qty_oracle : 0;
                $qc     = isset($cntso_grouped[$itemCode])    ? (int) $cntso_grouped[$itemCode]->qty_appkso    : 0;

                if ($grade === 'OE') {
                    $oracle_oe += $qo;
                    $appkso_oe += $qc;
                }
                if ($grade === 'OK') {
                    $oracle_ok += $qo;
                    $appkso_ok += $qc;
                }
            }

            $summary['progress_scan_oe'] = $oracle_oe > 0 ? round(($appkso_oe / $oracle_oe) * 100, 2) : 0;
            $summary['progress_scan_ok'] = $oracle_ok > 0 ? round(($appkso_ok / $oracle_ok) * 100, 2) : 0;


            return response()->json(['success' => true, 'data' => array_values($results), 'summary' => $summary]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function getDetailPricePattern(Request $request)
    {
        $so_name = $request->query('so_name');
        $pattern = $request->query('pattern');
        $gradeFilter = $request->query('grade');

        if (!$so_name) return response()->json(['html' => 'SO tidak ada']);

        $cntso = DB::connection('mysql_second')->table('cntso')->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');
        $snapshot = DB::table('snapshot_bpw')->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');

        $masterQuery = DB::table('so_all_wh_master_size_db');
        if ($pattern && $pattern !== 'ALL') $masterQuery->where('pattern', $pattern);
        if ($gradeFilter && $gradeFilter !== 'MIX') $masterQuery->where('grade', $gradeFilter);
        $master_map = $masterQuery->get();
        $price_map = DB::table('so_all_wh_price_db')->select(DB::raw('TRIM(item) as item'), 'price')->get()->keyBy('item');

        $data_minus = collect();
        $data_plus = collect();
        $rekap = [];
        $total = ['c_p' => 0, 'c_r' => 0, 'o_p' => 0, 'o_r' => 0, 'v_p' => 0, 'v_r' => 0, 'sku_m' => 0, 'sku_p' => 0];
        $tot_minus_pcs = $tot_plus_pcs = 0;
        $tot_minus_rp = $tot_plus_rp = 0;
        $total_variance_pcs = $total_variance_rp = 0;

        foreach ($master_map as $master) {
            $itemCode = trim($master->item);
            $qty_fisik = isset($cntso[$itemCode]) ? (int)$cntso[$itemCode]->qty : 0;
            $qty_oracle = isset($snapshot[$itemCode]) ? (int)$snapshot[$itemCode]->qty : 0;
            $variance = $qty_fisik - $qty_oracle;

            if ($qty_fisik == 0 && $qty_oracle == 0) continue;

            $price = ($price_map->get($itemCode)) ? (int)$price_map->get($itemCode)->price : 0;
            $var_rp = $variance * $price;

            $total_variance_pcs += $variance;
            $total_variance_rp += $var_rp;

            // Rekap untuk tabel atas
            $key = $master->pattern . ' (' . $master->grade . ')';
            if (!isset($rekap[$key])) $rekap[$key] = ['c_p' => 0, 'c_r' => 0, 'o_p' => 0, 'o_r' => 0, 'v_p' => 0, 'v_r' => 0, 'm' => 0, 'p' => 0];
            $rekap[$key]['c_p'] += $qty_fisik;
            $rekap[$key]['c_r'] += ($qty_fisik * $price);
            $rekap[$key]['o_p'] += $qty_oracle;
            $rekap[$key]['o_r'] += ($qty_oracle * $price);
            $rekap[$key]['v_p'] += $variance;
            $rekap[$key]['v_r'] += $var_rp;

            $row = (object)['pattern' => $master->pattern, 'item' => $itemCode, 'desc' => $master->description, 'qty_fisik' => $qty_fisik, 'fisik_rp' => $qty_fisik * $price, 'qty_oracle' => $qty_oracle, 'oracle_rp' => $qty_oracle * $price, 'variance' => $variance, 'variance_rp' => $var_rp];

            if ($variance < 0) {
                $data_minus->push($row);
                $tot_minus_pcs += abs($variance);
                $tot_minus_rp += abs($var_rp);
                $rekap[$key]['m']++;
                $total['sku_m']++;
            } elseif ($variance > 0) {
                $data_plus->push($row);
                $tot_plus_pcs += $variance;
                $tot_plus_rp += $var_rp;
                $rekap[$key]['p']++;
                $total['sku_p']++;
            }
        }

        // Hitung Grand Total
        foreach ($rekap as $r) {
            $total['c_p'] += $r['c_p'];
            $total['c_r'] += $r['c_r'];
            $total['o_p'] += $r['o_p'];
            $total['o_r'] += $r['o_r'];
            $total['v_p'] += $r['v_p'];
            $total['v_r'] += $r['v_r'];
        }

        // SESUDAH — sort by absolute PCS terbesar ke terkecil
        $data_minus = $data_minus->sortByDesc(fn($i) => abs((int)$i->variance))->values();
        $data_plus  = $data_plus->sortByDesc(fn($i) => abs((int)$i->variance))->values();

        // --- RENDER HTML ---
        $html = '<div class="mb-2 text-start"><span class="text-uppercase fw-bold" style="font-size: 11px;">PATTERN: ' . $pattern . ' (' . $gradeFilter . ') &nbsp;||&nbsp; Variance: ' . ($total_variance_pcs >= 0 ? '+' : '') . number_format($total_variance_pcs, 0, ',', '.') . ' PCS &nbsp;||&nbsp; Price: ' . number_format($total_variance_rp, 0, ',', '.') . ' Rupiah</span></div>';
        $html .= '<div class="table-responsive border border-dark mb-3"><table class="table table-bordered table-sm mb-0 align-middle text-center" style="font-size: 10px;">';
        $html .= '<thead style="background-color: #1e293b; color: white;"><tr><th rowspan="2">No</th><th rowspan="2">Pattern (Grade)</th><th colspan="2">Counted</th><th colspan="2">On-hand</th><th colspan="2">Variance</th><th rowspan="2">SKU (-)</th><th rowspan="2">SKU (+)</th></tr><tr><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th></tr></thead><tbody>';
        $i = 1;
        foreach ($rekap as $k => $r) {
            $html .= "<tr><td>{$i}</td><td class='text-start ps-2'>{$k}</td><td>" . number_format($r['c_p'], 0, ',', '.') . "</td><td>" . number_format($r['c_r'], 0, ',', '.') . "</td><td>" . number_format($r['o_p'], 0, ',', '.') . "</td><td>" . number_format($r['o_r'], 0, ',', '.') . "</td><td class='fw-bold " . ($r['v_p'] < 0 ? 'text-danger' : 'text-primary') . "'>" . number_format($r['v_p'], 0, ',', '.') . "</td><td class='fw-bold " . ($r['v_r'] < 0 ? 'text-danger' : 'text-primary') . "'>" . number_format($r['v_r'], 0, ',', '.') . "</td><td>{$r['m']}</td><td>{$r['p']}</td></tr>";
            $i++;
        }
        $html .= '<tr class="fw-bold" style="background-color: #e2e8f0;"><td>TOTAL</td><td></td><td>' . number_format($total['c_p'], 0, ',', '.') . '</td><td>' . number_format($total['c_r'], 0, ',', '.') . '</td><td>' . number_format($total['o_p'], 0, ',', '.') . '</td><td>' . number_format($total['o_r'], 0, ',', '.') . '</td><td>' . number_format($total['v_p'], 0, ',', '.') . '</td><td>' . number_format($total['v_r'], 0, ',', '.') . '</td><td>' . $total['sku_m'] . '</td><td>' . $total['sku_p'] . '</td></tr></tbody></table></div>';

        $html .= '<div class="row g-2">';

        // -- TABEL MINUS --
        $html .= '<div class="col-12 col-xl-6 d-flex flex-column">';
        $html .= '<div class="mb-1 text-start" style="font-size: 10px;">';
        $html .= 'DATA MINUS (-) : ' . $data_minus->count() . ' SKU &nbsp;||&nbsp; TOTAL VAR: -' . number_format($tot_minus_pcs, 0, ',', '.') . ' PCS &nbsp;||&nbsp; Price: -' . number_format($tot_minus_rp, 0, ',', '.') . ' Rupiah';
        $html .= '</div>';
        $html .= '<div class="table-responsive border border-dark" style="max-height: 45vh; overflow-y: auto;">';
        $html .= '<table class="table table-bordered table-sm mb-0 align-middle text-center" style="font-size: 10px;">';
        $html .= '<thead class="sticky-top bg-white">';
        $html .= '<tr><th rowspan="2">NO</th><th rowspan="2">PATTERN</th><th rowspan="2">ITEM CODE</th><th rowspan="2">DESCRIPTION</th><th colspan="2">COUNTED</th><th colspan="2">ON-HAND</th><th colspan="2">VAR</th></tr>';
        $html .= '<tr><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th></tr></thead><tbody>';
        foreach ($data_minus as $i => $row) {
            $html .= '<tr><td>' . ($i + 1) . '</td><td class="text-start">' . $row->pattern . '</td><td>' . $row->item . '</td><td class="text-start">' . $row->desc . '</td>';
            $html .= '<td class="text-end">' . number_format($row->qty_fisik, 0, ',', '.') . '</td><td class="text-end">' . number_format($row->fisik_rp, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end">' . number_format($row->qty_oracle, 0, ',', '.') . '</td><td class="text-end">' . number_format($row->oracle_rp, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-danger fw-bold">' . number_format($row->variance, 0, ',', '.') . '</td><td class="text-end text-danger fw-bold">' . number_format($row->variance_rp, 0, ',', '.') . '</td></tr>';
        }
        if ($data_minus->isEmpty()) $html .= '<tr><td colspan="10" class="text-muted p-2">Tidak ada data minus</td></tr>';
        $html .= '</tbody></table></div></div>';

        // -- TABEL PLUS --
        $html .= '<div class="col-12 col-xl-6 d-flex flex-column">';
        $html .= '<div class="mb-1 text-start" style="font-size: 10px;">';
        $html .= 'DATA PLUS (+) : ' . $data_plus->count() . ' SKU &nbsp;||&nbsp; TOTAL VAR: +' . number_format($tot_plus_pcs, 0, ',', '.') . ' PCS &nbsp;||&nbsp; Price: +' . number_format($tot_plus_rp, 0, ',', '.') . ' Rupiah';
        $html .= '</div>';
        $html .= '<div class="table-responsive border border-dark" style="max-height: 45vh; overflow-y: auto;">';
        $html .= '<table class="table table-bordered table-sm mb-0 align-middle text-center" style="font-size: 10px;">';
        $html .= '<thead class="sticky-top bg-white">';
        $html .= '<tr><th rowspan="2">NO</th><th rowspan="2">PATTERN</th><th rowspan="2">ITEM CODE</th><th rowspan="2">DESCRIPTION</th><th colspan="2">COUNTED</th><th colspan="2">ON-HAND</th><th colspan="2">VAR</th></tr>';
        $html .= '<tr><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th><th>Pcs</th><th>Rp</th></tr></thead><tbody>';
        foreach ($data_plus as $i => $row) {
            $html .= '<tr><td>' . ($i + 1) . '</td><td class="text-start">' . $row->pattern . '</td><td>' . $row->item . '</td><td class="text-start">' . $row->desc . '</td>';
            $html .= '<td class="text-end">' . number_format($row->qty_fisik, 0, ',', '.') . '</td><td class="text-end">' . number_format($row->fisik_rp, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end">' . number_format($row->qty_oracle, 0, ',', '.') . '</td><td class="text-end">' . number_format($row->oracle_rp, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-primary fw-bold">+' . number_format($row->variance, 0, ',', '.') . '</td><td class="text-end text-primary fw-bold">+' . number_format($row->variance_rp, 0, ',', '.') . '</td></tr>';
        }
        if ($data_plus->isEmpty()) $html .= '<tr><td colspan="10" class="text-muted p-2">Tidak ada data plus</td></tr>';
        $html .= '</tbody></table></div></div>';

        $html .= '</div>'; // End Row

        return response()->json(['html' => $html]);
    }

    public function getDetailPattern(Request $request)
    {
        $so_name     = $request->query('so_name');
        $pattern     = $request->query('pattern');
        $gradeFilter = $request->query('grade');
        $ctx         = $request->query('ctx', 'ptn');

        if (!$so_name) return response()->json(['html' => 'SO tidak ada']);

        $cntso = DB::connection('mysql_second')->table('cntso')
            ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))
            ->where('so_name', $so_name)
            ->groupBy(DB::raw('TRIM(ItemCode)'))
            ->get()->keyBy('ItemCode');

        $snapshot = DB::table('snapshot_bpw')
            ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))
            ->where('so_name', $so_name)
            ->groupBy(DB::raw('TRIM(ItemCode)'))
            ->get()->keyBy('ItemCode');

        $masterQuery = DB::table('so_all_wh_master_size_db');
        if ($pattern && $pattern !== 'ALL') $masterQuery->where('pattern', $pattern);
        if ($gradeFilter && $gradeFilter !== 'MIX') $masterQuery->where('grade', $gradeFilter);
        $master_map = $masterQuery->get()->keyBy('item');

        // ★ AMBIL DESCRIPTION DARI master_items — 2 map berbeda
        // Map 1: item_code → untuk lookup item UTAMA (ItemCode di similar = item_code di master_items)
        $masterItems = DB::table('master_items')
            ->select('item_code', 'item_code_desc', 'description')
            ->get();

        $desc_by_item_code = $masterItems->keyBy('item_code');
        $desc_by_item_code_desc = $masterItems->keyBy('item_code_desc');

        // ★ AMBIL SEMUA RELASI SIMILAR (TANPA FILTER) — penting biar relasi 2 arah lengkap
        $similar_relations = DB::table('master_item_similar')->get();
        $similar_map = [];
        foreach ($similar_relations as $sim) {
            $similar_map[trim($sim->ItemCode)][]        = trim($sim->ItemCodeSimilar);
            $similar_map[trim($sim->ItemCodeSimilar)][] = trim($sim->ItemCode);
        }

        $data_minus     = collect();
        $data_plus      = collect();
        $tot_minus_pcs  = $tot_plus_pcs = 0;
        $total_variance = 0;

        foreach ($master_map as $itemCode => $master) {
            $qty_fisik  = isset($cntso[$itemCode])    ? (int)$cntso[$itemCode]->qty    : 0;
            $qty_oracle = isset($snapshot[$itemCode]) ? (int)$snapshot[$itemCode]->qty : 0;
            $variance   = $qty_fisik - $qty_oracle;

            if ($variance == 0) continue;
            $total_variance += $variance;

            // ★ DESCRIPTION item utama: lookup via item_code
            $desc = isset($desc_by_item_code[$itemCode])
                ? ($desc_by_item_code[$itemCode]->description ?? '')
                : '';
            if (empty(trim($desc))) {
                $desc = $master->description ?? '';
            }
            if (empty(trim($desc))) {
                $desc = '-';
            }

            // ★ CARI SIMILAR
            $similar_list = [];
            if (isset($similar_map[$itemCode])) {
                foreach (array_unique($similar_map[$itemCode]) as $simCode) {
                    $simCode     = trim($simCode);
                    $sim_counted = isset($cntso[$simCode])    ? (int)$cntso[$simCode]->qty    : 0;
                    $sim_onhand  = isset($snapshot[$simCode]) ? (int)$snapshot[$simCode]->qty : 0;
                    $sim_var     = $sim_counted - $sim_onhand;

                    if ($sim_counted == 0 && $sim_onhand == 0 && $sim_var == 0) {
                        continue;
                    }

                    $sim_master = isset($desc_by_item_code_desc[$simCode])
                        ? $desc_by_item_code_desc[$simCode]
                        : null;
                    $sim_desc = $sim_master ? ($sim_master->description ?? '-') : '-';

                    $similar_list[] = [
                        'item_code'   => $simCode,
                        'description' => $sim_desc,
                        'counted'     => $sim_counted,
                        'onhand'      => $sim_onhand,
                        'variance'    => $sim_var,
                    ];
                }
            }

            $row = (object)[
                'pattern'      => $master->pattern ?? '-',
                'item'         => $itemCode,
                'desc'         => $desc,
                'qty_fisik'    => $qty_fisik,
                'qty_oracle'   => $qty_oracle,
                'variance'     => $variance,
                'similar_list' => $similar_list,
            ];

            if ($variance < 0) {
                $data_minus->push($row);
                $tot_minus_pcs += abs($variance);
            } else {
                $data_plus->push($row);
                $tot_plus_pcs += $variance;
            }
        }

        $data_minus = $data_minus->sortBy([['variance', 'asc'],  ['item', 'asc']])->values();
        $data_plus  = $data_plus->sortBy([['variance', 'desc'], ['item', 'asc']])->values();

        // ★ HELPER: render collapse row similar
        $renderSimilarRow = function (string $rowId, array $similar_list) use ($ctx): string {
            if (empty($similar_list)) return '';
            $prefixedId = $ctx . '-' . $rowId;

            $rows = '';
            foreach ($similar_list as $s) {
                $varClass = $s['variance'] < 0 ? 'text-danger'
                    : ($s['variance'] > 0 ? 'text-primary' : 'text-muted');
                $varSign  = $s['variance'] > 0 ? '+' : '';
                $rows .= '
        <tr style="background-color: transparent;">
            <td colspan="2" class="border-0"></td>
            <td class="fw-bold ps-3 border-0" style="color:#d97706;">↳ ' . htmlspecialchars($s['item_code']) . '</td>
            <td class="text-muted fw-bold border-0">' . htmlspecialchars($s['description']) . '</td>
            <td class="text-end border-0">' . number_format($s['counted'], 0, ',', '.') . '</td>
            <td class="text-end text-muted border-0">' . number_format($s['onhand'], 0, ',', '.') . '</td>
            <td class="text-end fw-bold border-0 ' . $varClass . '">' . $varSign . number_format($s['variance'], 0, ',', '.') . '</td>
        </tr>';
            }

            // Desain Kotak Nested: margin, rounded, border keliling, shadow, dan background beda
            return '
                    <tr id="similar-' . $prefixedId . '" class="similar-collapse-row" style="display:none; background-color: #f8fafc;">
                        <td colspan="7" class="p-3 border-bottom">
                            <div class="rounded-3 shadow-sm" style="border: 1px solid #fcd34d; border-left: 5px solid #f59e0b; background-color: #fffbeb; margin-left: 3rem; margin-right: 1rem; overflow: hidden;">
                                <table class="table table-sm mb-0" style="font-size:10px;">
                                    <thead style="background-color: #fde68a; border-bottom: 2px solid #fcd34d;">
                                        <tr class="text-center text-dark">
                                            <th colspan="2" class="border-0" style="width: 5%;"></th>
                                            <th class="text-start ps-3 border-0">📌 ITEM SIMILAR</th>
                                            <th class="text-start border-0">DESCRIPTION</th>
                                            <th class="border-0">COUNTED</th>
                                            <th class="border-0">ON-HAND</th>
                                            <th class="border-0">VAR</th>
                                        </tr>
                                    </thead>
                                    <tbody>' . $rows . '</tbody>
                                </table>
                            </div>
                        </td>
                    </tr>';
        };

        // ★ HELPER: badge S
        $badgeS = function (string $rowId, array $similar_list) use ($ctx): string {
            if (empty($similar_list)) return '';
            $prefixedId = $ctx . '-' . $rowId;
            return ' <span class="badge similar-badge"
                style="background:#f59e0b;color:#000;font-size:9px;cursor:pointer;user-select:none;vertical-align:middle;"
                onclick="event.stopPropagation(); window.toggleSimilarRow(\'' . $prefixedId . '\', this)"
                title="' . count($similar_list) . ' Item Similar">S</span>';
        };

        $html = '<div class="row g-3">';

        // --- TABEL MINUS ---
        $html .= '<div class="col-12 col-xl-6 d-flex flex-column"><div class="card border-0 shadow-sm flex-grow-1">';
        $html .= '<div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">';
        $html .= '<span class="fw-bold">DATA MINUS (-) : ' . $data_minus->count() . ' SKU</span>';
        $html .= '<span class="badge bg-white text-danger fs-6 border border-danger">TOTAL VAR: -' . number_format($tot_minus_pcs, 0, ',', '.') . ' PCS</span></div>';
        $html .= '<div class="table-responsive" style="max-height:calc(105vh - 200px); overflow-y:auto;">';
        $html .= '<table class="table table-bordered table-hover mb-0" style="font-size:11px;">';
        $html .= '<thead class="bg-light text-center align-middle sticky-top" style="z-index:2;"><tr>';
        $html .= '<th style="width:5%;">NO</th><th>PATTERN</th><th>ITEM CODE</th><th>DESCRIPTION</th><th>COUNTED</th><th>ON-HAND</th><th>VAR</th>';
        $html .= '</tr></thead><tbody class="align-middle">';

        foreach ($data_minus as $i => $row) {
            $rowId = 'minus-' . $i;
            $html .= '<tr style="cursor:pointer;background:#fff;" onclick="window.openModalScanHistory(\'' . addslashes($row->item) . '\', \'' . addslashes($row->desc) . '\')">';
            $html .= '<td class="text-center text-muted">' . ($i + 1) . '</td>';
            $html .= '<td class="text-start">' . htmlspecialchars($row->pattern) . '</td>';
            $html .= '<td class="fw-bold" style="color:#dc2626;">' . htmlspecialchars($row->item) . $badgeS($rowId, $row->similar_list) . '</td>';
            $html .= '<td class="text-start" style="max-width:200px;">' . htmlspecialchars($row->desc) . '</td>';
            $html .= '<td class="text-end fw-bold">' . number_format($row->qty_fisik, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-muted">' . number_format($row->qty_oracle, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-danger fw-bold">' . number_format($row->variance, 0, ',', '.') . '</td>';
            $html .= '</tr>';
            $html .= $renderSimilarRow($rowId, $row->similar_list);
        }

        if ($data_minus->isEmpty()) $html .= '<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada data minus</td></tr>';
        $html .= '</tbody></table></div></div></div>';

        // --- TABEL PLUS ---
        $html .= '<div class="col-12 col-xl-6 d-flex flex-column"><div class="card border-0 shadow-sm flex-grow-1">';
        $html .= '<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">';
        $html .= '<span class="fw-bold">DATA PLUS (+) : ' . $data_plus->count() . ' SKU</span>';
        $html .= '<span class="badge bg-white text-primary fs-6 border border-primary">TOTAL VAR: +' . number_format($tot_plus_pcs, 0, ',', '.') . ' PCS</span></div>';
        $html .= '<div class="table-responsive" style="max-height:calc(105vh - 200px); overflow-y:auto;">';
        $html .= '<table class="table table-bordered table-hover mb-0" style="font-size:11px;">';
        $html .= '<thead class="bg-light text-center align-middle sticky-top" style="z-index:2;"><tr>';
        $html .= '<th style="width:5%;">NO</th><th>PATTERN</th><th>ITEM CODE</th><th>DESCRIPTION</th><th>COUNTED</th><th>ON-HAND</th><th>VAR</th>';
        $html .= '</tr></thead><tbody class="align-middle">';

        foreach ($data_plus as $i => $row) {
            $rowId = 'plus-' . $i;
            $html .= '<tr style="cursor:pointer;background:#fff;" onclick="window.openModalScanHistory(\'' . addslashes($row->item) . '\', \'' . addslashes($row->desc) . '\')">';
            $html .= '<td class="text-center text-muted">' . ($i + 1) . '</td>';
            $html .= '<td class="fw-bold">' . htmlspecialchars($row->pattern) . '</td>';
            $html .= '<td class="text-primary fw-bold">' . htmlspecialchars($row->item) . $badgeS($rowId, $row->similar_list) . '</td>';
            $html .= '<td class="text-start" style="max-width:200px;">' . htmlspecialchars($row->desc) . '</td>';
            $html .= '<td class="text-end fw-bold">' . number_format($row->qty_fisik, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-muted">' . number_format($row->qty_oracle, 0, ',', '.') . '</td>';
            $html .= '<td class="text-end text-primary fw-bold">+' . number_format($row->variance, 0, ',', '.') . '</td>';
            $html .= '</tr>';
            $html .= $renderSimilarRow($rowId, $row->similar_list);
        }

        if ($data_plus->isEmpty()) $html .= '<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada data plus</td></tr>';
        $html .= '</tbody></table></div></div></div></div>';

        return response()->json([
            'html' => $html,
            'total_variance' => $total_variance
        ]);
    }

    public function getUnscannedItems(Request $request)
    {
        $so_name = $request->query('so_name');
        if (!$so_name) return response()->json(['data' => []]);

        // Ambil semua item dari snapshot (on-hand oracle)
        $snapshot = DB::table('snapshot_bpw')
            ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty_oracle'))
            ->where('so_name', $so_name)
            ->groupBy(DB::raw('TRIM(ItemCode)'))
            ->get()
            ->keyBy('ItemCode');

        // Ambil semua item yang sudah di-scan di cntso
        $cntso = DB::connection('mysql_second')->table('cntso')
            ->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty_counted'))
            ->where('so_name', $so_name)
            ->groupBy(DB::raw('TRIM(ItemCode)'))
            ->get()
            ->keyBy('ItemCode');

        // Master untuk mapping grade & description
        $master_map = DB::table('so_all_wh_master_size_db')
            ->select(DB::raw('TRIM(item) as item'), 'description', 'grade')
            ->get()
            ->keyBy('item');

        $result = [];

        foreach ($snapshot as $itemCode => $snap) {
            $qty_oracle  = (int) $snap->qty_oracle;
            $qty_counted = isset($cntso[$itemCode]) ? (int) $cntso[$itemCode]->qty_counted : 0;
            $qty_sisa    = $qty_counted - $qty_oracle;
            $persen      = $qty_oracle > 0 ? round(($qty_counted / $qty_oracle) * 100, 1) : 0;

            // Hanya tampilkan yang belum 100% atau masih ada sisa
            if ($qty_oracle <= 0) continue;

            $master = $master_map->get($itemCode);

            $result[] = [
                'item'        => $itemCode,
                'description' => $master->description ?? '-',
                'grade'       => $master ? strtoupper(trim($master->grade)) : 'UNKNOWN',
                'qty_oracle'  => $qty_oracle,
                'qty_counted' => $qty_counted,
                'qty_sisa'    => $qty_sisa,
                'persen'      => $persen,
            ];
        }

        // Sort: sisa terbesar dulu
        usort($result, fn($a, $b) => $a['qty_sisa'] <=> $b['qty_sisa']);

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function getScanHistory(Request $request)
    {
        $so_name = $request->query('so_name');
        $item_code = $request->query('item_code');

        if (!$so_name || !$item_code) return response()->json(['html' => 'Data tidak lengkap']);

        try {
            // 1. Ambil data scan
            $scans = DB::connection('mysql_second')->table('cntso')
                ->select('NoDoc', 'ItemCode', 'QtyStk', 'txndate', 'opr')
                ->where('so_name', $so_name)
                ->where('ItemCode', $item_code)
                ->orderBy('txndate', 'desc')
                ->get();

            if ($scans->isEmpty()) {
                return response()->json(['html' => '<div class="alert alert-warning text-center m-3">Belum ada data scan.</div>']);
            }

            // 2. Kumpulkan OPR unik
            $opr_list = $scans->pluck('opr')->unique()->filter()->toArray();

            // 3. Ambil mapping dari server kedua (Benerin kolom jadi oprcode)
            $pic_map = DB::connection('mysql_second')->table('bcmcfgv1.oprbld')
                ->select(DB::raw('TRIM(oprcode) as opcode'), 'oprname')
                ->whereIn(DB::raw('TRIM(oprcode)'), $opr_list)
                ->get()
                ->keyBy('opcode');

            // 4. Ambil Deskripsi
            $master_item = DB::table('so_all_wh_master_size_db')
                ->where('item', $item_code)
                ->first();
            $description = $master_item ? $master_item->description : '-';

            // 5. Render HTML
            $html = '<div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">';
            $html .= '<table class="table table-bordered table-hover table-sm mb-0 align-middle" style="font-size:11px;">';
            $html .= '<thead class="bg-dark text-white text-center sticky-top"><tr>';
            $html .= '<th>NO</th><th>NO DOC</th><th>ITEM CODE</th><th>DESCRIPTION</th><th>QTY STK</th><th>TXN DATE</th><th>OPR (NIK)</th><th>NAMA PIC</th>';
            $html .= '</tr></thead><tbody>';

            $no = 1;
            foreach ($scans as $scan) {
                $opr_clean = trim($scan->opr);
                // Panggil oprname
                $nama = isset($pic_map[$opr_clean]) ? $pic_map[$opr_clean]->oprname : '<span class="text-danger fst-italic">Unregistered</span>';

                $html .= '<tr class="text-center">';
                $html .= '<td class="text-muted">' . $no++ . '</td>';
                $html .= '<td class="fw-bold">' . $scan->NoDoc . '</td>';
                $html .= '<td class="fw-bold text-primary">' . $scan->ItemCode . '</td>';
                $html .= '<td class="text-start text-truncate" style="max-width: 200px;" title="' . htmlspecialchars($description, ENT_QUOTES) . '">' . $description . '</td>';
                $html .= '<td class="fw-black text-success" style="font-size:12px;">' . number_format($scan->QtyStk, 0, ',', '.') . '</td>';
                $html .= '<td>' . $scan->txndate . '</td>';
                $html .= '<td>' . $opr_clean . '</td>';
                $html .= '<td class="text-start fw-semibold">' . $nama . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['html' => '<div class="alert alert-danger text-center m-3">Error: ' . $e->getMessage() . '</div>']);
        }
    }

    public function getDetailPPM(Request $request)
    {
        $so_name = $request->query('so_name');
        if (!$so_name) return "SO tidak ditemukan";

        $cntso = DB::connection('mysql_second')->table('cntso')->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');
        $snapshot = DB::table('snapshot_bpw')->select(DB::raw('TRIM(ItemCode) as ItemCode'), DB::raw('SUM(QtyStk) as qty'))->where('so_name', $so_name)->groupBy(DB::raw('TRIM(ItemCode)'))->get()->keyBy('ItemCode');
        $master_map = DB::table('so_all_wh_master_size_db')->select(DB::raw('TRIM(item) as item'), 'product', 'grade')->get();

        $ppm_data = [];
        $tot_oracle = 0;
        $tot_var = 0;

        foreach ($master_map as $master) {
            $item = $master->item;
            $product = strtoupper(trim($master->product)) ?: 'LAINNYA';
            $grade = strtoupper(trim($master->grade));
            if (!in_array($grade, ['OE', 'OK', '2ND'])) $grade = '2ND';

            if (!isset($ppm_data[$product])) {
                $ppm_data[$product] = ['OE' => ['o' => 0, 'c' => 0, 'v' => 0], 'OK' => ['o' => 0, 'c' => 0, 'v' => 0], '2ND' => ['o' => 0, 'c' => 0, 'v' => 0]];
            }

            $qo = isset($snapshot[$item]) ? $snapshot[$item]->qty : 0;
            $qc = isset($cntso[$item]) ? $cntso[$item]->qty : 0;
            $var = ($qc - $qo);

            $ppm_data[$product][$grade]['o'] += $qo;
            $ppm_data[$product][$grade]['c'] += $qc;
            $ppm_data[$product][$grade]['v'] += $var;

            $tot_oracle += $qo;
            $tot_var += $var;
        }

        // --- RENDER HTML DENGAN STYLE BARU + PPM PER PRODUCT ---
        $html = '<div class="text-center mb-3">';
        $html .= '<h5 class="fw-bold text-uppercase">REKAPITULASI HASIL STOCK OPNAME INTERNAL</h5>';
        $html .= '<h6 class="text-muted">' . htmlspecialchars($so_name) . '</h6>';
        $html .= '</div>';

        $html .= '<table class="table table-bordered table-sm text-center align-middle" style="font-size:11px;">';
        $html .= '<thead style="background-color: #1e293b; color: white;">';
        $html .= '<tr><th rowspan="2" class="align-middle">Product</th><th colspan="3">OE</th><th colspan="3">OK</th><th colspan="3">2nd</th><th colspan="3">TOTAL</th><th rowspan="2" class="align-middle">PPM</th></tr>';
        $html .= '<tr><th>On Hand</th><th>Counted</th><th>Var</th><th>On Hand</th><th>Counted</th><th>Var</th><th>On Hand</th><th>Counted</th><th>Var</th><th>On Hand</th><th>Counted</th><th>Var</th></tr></thead><tbody>';

        $gr = ['OE' => ['o' => 0, 'c' => 0, 'v' => 0], 'OK' => ['o' => 0, 'c' => 0, 'v' => 0], '2ND' => ['o' => 0, 'c' => 0, 'v' => 0], 'TOTAL' => ['o' => 0, 'c' => 0, 'v' => 0]];

        foreach ($ppm_data as $prod => $d) {
            $t_o = $d['OE']['o'] + $d['OK']['o'] + $d['2ND']['o'];
            $t_c = $d['OE']['c'] + $d['OK']['c'] + $d['2ND']['c'];
            $t_v = $d['OE']['v'] + $d['OK']['v'] + $d['2ND']['v'];

            if ($t_o == 0 && $t_c == 0) continue;

            // Hitung PPM per product: (abs(variance) / on_hand) * 1.000.000
            $ppm_product = $t_o > 0 ? number_format((abs($t_v) / $t_o) * 1000000, 2, ',', '.') : '0,00';

            $html .= "<tr><td class='fw-bold text-start'>$prod</td>";
            foreach (['OE', 'OK', '2ND'] as $g) {
                $html .= "<td>" . number_format($d[$g]['o'], 0, ',', '.') . "</td><td>" . number_format($d[$g]['c'], 0, ',', '.') . "</td><td class='text-danger'>" . number_format($d[$g]['v'], 0, ',', '.') . "</td>";
                $gr[$g]['o'] += $d[$g]['o'];
                $gr[$g]['c'] += $d[$g]['c'];
                $gr[$g]['v'] += $d[$g]['v'];
            }
            $html .= "<td class='fw-bold'>" . number_format($t_o, 0, ',', '.') . "</td><td class='fw-bold'>" . number_format($t_c, 0, ',', '.') . "</td><td class='fw-bold text-danger'>" . number_format($t_v, 0, ',', '.') . "</td>";
            $html .= "<td class='fw-bold text-primary'>$ppm_product</td></tr>";
            $gr['TOTAL']['o'] += $t_o;
            $gr['TOTAL']['c'] += $t_c;
            $gr['TOTAL']['v'] += $t_v;
        }

        // BARIS TOTAL ALL (Warna Header)
        $ppm_total = $gr['TOTAL']['o'] > 0 ? number_format((abs($gr['TOTAL']['v']) / $gr['TOTAL']['o']) * 1000000, 2, ',', '.') : '0,00';
        $html .= '<tr style="background-color: #1e293b; color: white; font-weight: bold;">';
        $html .= '<td class="text-start">TOTAL ALL</td>';
        foreach (['OE', 'OK', '2ND', 'TOTAL'] as $g) {
            $html .= "<td>" . number_format($gr[$g]['o'], 0, ',', '.') . "</td><td>" . number_format($gr[$g]['c'], 0, ',', '.') . "</td><td>" . number_format($gr[$g]['v'], 0, ',', '.') . "</td>";
        }
        $html .= "<td>$ppm_total</td></tr></tbody></table>";

        // CARD PPM (Rata Tengah)
        $total_abs_var = abs($gr['TOTAL']['v']);
        $ppm_rate = $gr['TOTAL']['o'] > 0 ? number_format(($total_abs_var / $gr['TOTAL']['o']) * 1000000, 2, ',', '.') : 0;

        $html .= "<div class='d-flex justify-content-center'>"; // Rata Tengah
        $html .= "<div class='mt-3 p-3 bg-white border rounded shadow-sm text-center' style='max-width: 500px;'>
                <h5 class='text-danger fw-black mb-1'>PPM : $ppm_rate</h5>
                <small class='text-muted'>Detail Perhitungan: <br>( Total Variance : " . number_format($total_abs_var, 0, ',', '.') . " ) / ( Total On Hand : " . number_format($gr['TOTAL']['o'], 0, ',', '.') . " ) × 1.000.000</small>
              </div></div>";

        return $html;
    }
}
