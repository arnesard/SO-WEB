<?php

namespace App\Http\Controllers\so_karantina;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoKarantinaController extends Controller
{
    public function index()
    {
        $grades = DB::table('master_items')
            ->select('grade')
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->where('grade', '!=', '-')
            ->distinct()
            ->orderBy('grade', 'asc')
            ->pluck('grade');

        return view('so_karantina.so_karantina', compact('grades'));
    }

    public function getData(Request $request)
    {
        $grade = $request->input('grade');
        $search = $request->input('search');
        $keterangan_filter = $request->input('keterangan'); // Tangkap filter keterangan

        $bindings = [];
        $whereClause = "WHERE 1=1";

        if (!empty($grade)) {
            $whereClause .= " AND m.grade = ?";
            $bindings[] = $grade;
        }

        if (!empty($search)) {
            $whereClause .= " AND (items.item_code_desc LIKE ? OR m.description LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        // ==========================================================
        // 1. DATA TABEL UTAMA
        // ==========================================================
        $sql = "
            SELECT
                items.item_code_desc,
                m.description,
                m.grade,
                COALESCE(b.shift_1, 0) as shift_1,
                COALESCE(b.shift_2, 0) as shift_2,
                COALESCE(b.shift_3, 0) as shift_3,
                COALESCE(b.total_bstb, 0) as total_bstb,
                COALESCE(s.total_scan, 0) as total_scan
            FROM (
                SELECT TRIM(item_code_desc) as item_code_desc FROM so_karantina_bstb
                UNION
                SELECT TRIM(item_code_desc) as item_code_desc FROM so_karantina_scan
            ) items
            LEFT JOIN (
                SELECT
                    TRIM(item_code_desc) as item_code_desc,
                    SUM(CASE WHEN shift = 'I' THEN QtyStk ELSE 0 END) as shift_1,
                    SUM(CASE WHEN shift = 'II' THEN QtyStk ELSE 0 END) as shift_2,
                    SUM(CASE WHEN shift = 'III' THEN QtyStk ELSE 0 END) as shift_3,
                    SUM(QtyStk) as total_bstb
                FROM so_karantina_bstb
                GROUP BY TRIM(item_code_desc)
            ) b ON items.item_code_desc = b.item_code_desc
            LEFT JOIN (
                SELECT
                    TRIM(item_code_desc) as item_code_desc,
                    SUM(QtyStk) as total_scan
                FROM so_karantina_scan
                GROUP BY TRIM(item_code_desc)
            ) s ON items.item_code_desc = s.item_code_desc
            LEFT JOIN master_items m ON items.item_code_desc = TRIM(m.item_code_desc)
            $whereClause
        ";

        $data = DB::select($sql, $bindings);

        $tempResult = [];
        $varOE = ['sku' => 0, 'total' => 0];
        $varOK = ['sku' => 0, 'total' => 0];

        foreach ($data as $row) {
            $variance = $row->total_scan - $row->total_bstb;
            $keterangan = ($variance == 0) ? 'Sesuai' : 'Tidak Sesuai';

            // Hitung Variance Card (Tetap dihitung semua meskipun tabel di-filter)
            if ($variance != 0) {
                if ($row->grade === 'OE') {
                    $varOE['sku']++;
                    $varOE['total'] += $variance;
                } elseif ($row->grade === 'OK') {
                    $varOK['sku']++;
                    $varOK['total'] += $variance;
                }
            }

            // Eksekusi Filter Keterangan untuk Tabel
            if (!empty($keterangan_filter) && $keterangan !== $keterangan_filter) {
                continue; // Skip baris ini jika tidak cocok dengan filter
            }

            $tempResult[] = [
                'item_code'        => $row->item_code_desc,
                'item_description' => $row->description ?? '-',
                'shift_1'          => $row->shift_1,
                'shift_2'          => $row->shift_2,
                'shift_3'          => $row->shift_3,
                'total'            => $row->total_bstb,
                'hasil_scan'       => $row->total_scan,
                'variance'         => $variance,
                'keterangan'       => $keterangan,
            ];
        }

        // ==========================================================
        // FITUR SORTING VARIANCE (Terbesar -> Terkecil)
        // ==========================================================
        usort($tempResult, function ($a, $b) {
            return $a['variance'] <=> $b['variance'];
        });

        // Setel ulang nomor urut setelah di-sort
        $result = [];
        $no = 1;
        foreach ($tempResult as $item) {
            $item['no'] = $no++;
            $result[] = $item;
        }

        // ==========================================================
        // 2. DATA SUMMARY CARDS (OE, OK, PLANT)
        // ==========================================================
        $summaryOE = DB::selectOne("
            SELECT COUNT(DISTINCT TRIM(b.item_code_desc)) as sku, COALESCE(SUM(b.QtyStk), 0) as total
            FROM so_karantina_bstb b
            JOIN master_items m ON TRIM(b.item_code_desc) = TRIM(m.item_code_desc)
            WHERE m.grade = 'OE'
        ");

        $summaryOK = DB::selectOne("
            SELECT COUNT(DISTINCT TRIM(b.item_code_desc)) as sku, COALESCE(SUM(b.QtyStk), 0) as total
            FROM so_karantina_bstb b
            JOIN master_items m ON TRIM(b.item_code_desc) = TRIM(m.item_code_desc)
            WHERE m.grade = 'OK'
        ");

        $plantGradesRaw = DB::select("
            SELECT
                b.plant,
                COALESCE(m.grade, '-') as grade,
                COUNT(DISTINCT TRIM(b.item_code_desc)) as sku,
                COALESCE(SUM(b.QtyStk), 0) as total
            FROM so_karantina_bstb b
            LEFT JOIN master_items m ON TRIM(b.item_code_desc) = TRIM(m.item_code_desc)
            WHERE b.plant IS NOT NULL AND b.plant != ''
            GROUP BY b.plant, m.grade
            ORDER BY b.plant ASC, m.grade ASC
        ");

        $summaryPlant = [];
        foreach ($plantGradesRaw as $row) {
            $plant = strtoupper($row->plant);
            if (!isset($summaryPlant[$plant])) {
                $summaryPlant[$plant] = ['plant' => $plant, 'total' => 0, 'sku' => 0, 'grades' => []];
            }
            $summaryPlant[$plant]['grades'][] = ['grade' => $row->grade, 'qty' => $row->total, 'sku' => $row->sku];
            $summaryPlant[$plant]['total'] += $row->total;
            $summaryPlant[$plant]['sku'] += $row->sku;
        }

        // ==========================================================
        // 3. DATA RAW UNTUK EXPORT EXCEL PER PLANT
        // ==========================================================
        $exportSql = "
            SELECT
                UPPER(b.plant) as plant,
                TRIM(b.item_code_desc) as item_code_desc,
                m.description,
                SUM(CASE WHEN b.shift = 'I' THEN b.QtyStk ELSE 0 END) as shift_1,
                SUM(CASE WHEN b.shift = 'II' THEN b.QtyStk ELSE 0 END) as shift_2,
                SUM(CASE WHEN b.shift = 'III' THEN b.QtyStk ELSE 0 END) as shift_3,
                SUM(b.QtyStk) as total_bstb
            FROM so_karantina_bstb b
            LEFT JOIN master_items m ON TRIM(b.item_code_desc) = TRIM(m.item_code_desc)
            WHERE b.plant IS NOT NULL AND b.plant != ''
        ";

        $exportBindings = [];
        if (!empty($grade)) {
            $exportSql .= " AND m.grade = ?";
            $exportBindings[] = $grade;
        }
        if (!empty($search)) {
            $exportSql .= " AND (TRIM(b.item_code_desc) LIKE ? OR m.description LIKE ?)";
            $exportBindings[] = "%{$search}%";
            $exportBindings[] = "%{$search}%";
        }

        $exportSql .= " GROUP BY UPPER(b.plant), TRIM(b.item_code_desc), m.description ORDER BY UPPER(b.plant), TRIM(b.item_code_desc)";
        $exportPlantRaw = DB::select($exportSql, $exportBindings);

        return response()->json([
            'data'         => $result,
            'summaryOE'    => $summaryOE,
            'summaryOK'    => $summaryOK,
            'summaryPlant' => $summaryPlant,
            'varOE'        => $varOE,
            'varOK'        => $varOK,
            'exportPlant'  => $exportPlantRaw
        ]);
    }

    // ==========================================================
    // 4. MENGAMBIL DETAIL SCAN SAAT BARIS TABEL DIKLIK
    // ==========================================================
    public function getScanDetail(Request $request)
    {
        try {
            // Kasih default value '' (string kosong) biar gak error kalau datanya kebetulan null
            $itemCode = $request->input('item_code', '');

            $sql = "
                SELECT
                    s.opr,
                    p.nama as nama_opr,
                    s.NoDoc,
                    s.item_code_desc,
                    m.description,
                    s.QtyStk
                FROM so_karantina_scan s
                LEFT JOIN master_items m ON TRIM(s.item_code_desc) = TRIM(m.item_code_desc)
                -- Tambahin BINARY buat maksa JOIN kalau ada perbedaan Collation MySQL
                LEFT JOIN so_all_wh_pic_stock_db p ON BINARY s.opr = BINARY p.no_penneng
                WHERE TRIM(s.item_code_desc) = ?
                ORDER BY s.id ASC
            ";

            $data = DB::select($sql, [trim($itemCode)]);

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            // Lempar pesan error yang sebenarnya biar gampang di-debug di Frontend
            return response()->json([
                'success' => false,
                'message' => 'Error SQL: ' . $e->getMessage()
            ], 500);
        }
    }
}
