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

    // Ambil daftar nama operator dari so_karantina_scan JOIN so_all_wh_pic_stock_db
    public function getOperatorList()
    {
        $operators = DB::select("
        SELECT DISTINCT
            BINARY p.no_penneng as no_penneng,
            p.nama
        FROM so_karantina_scan s
        LEFT JOIN so_all_wh_pic_stock_db p ON BINARY s.opr = BINARY p.no_penneng
        WHERE p.nama IS NOT NULL AND p.nama != ''
        ORDER BY p.nama ASC
    ");

        return response()->json(['data' => $operators]);
    }
 public function getRekapByOperator(Request $request)
{
    try {
        $opr = $request->input('opr');

        $data = DB::select("
            SELECT
                s.item_code_desc,
                m.description,
                SUM(s.QtyStk) as total_scan,
                MIN(s.NoDoc) as sample_nokso
            FROM so_karantina_scan s
            LEFT JOIN master_items m ON TRIM(s.item_code_desc) = TRIM(m.item_code_desc)
            LEFT JOIN so_all_wh_pic_stock_db p ON BINARY s.opr = BINARY p.no_penneng
            WHERE p.nama = ?
            GROUP BY s.item_code_desc, m.description
            ORDER BY s.item_code_desc ASC
        ", [$opr]);

        // Ambil list auditor
        $auditorList = DB::table('so_all_wh_pic_auditor_db')
            ->select('nama', 'gedung', 'lot')
            ->get();

        // ── Ambil SEMUA NoDoc milik operator ini ──
        $allNoDocs = DB::select("
            SELECT DISTINCT s.NoDoc
            FROM so_karantina_scan s
            LEFT JOIN so_all_wh_pic_stock_db p ON BINARY s.opr = BINARY p.no_penneng
            WHERE p.nama = ?
        ", [$opr]);

        // ── Mapping semua NoDoc → auditor unik ──
        $auditorNames = collect($allNoDocs)
            ->map(fn($row) => $this->findAuditorByNokso($row->NoDoc ?? '', $auditorList))
            ->filter(fn($nama) => $nama && $nama !== '-')
            ->unique()
            ->values()
            ->toArray();

        // Inject auditor per baris
        $data = array_map(function ($row) use ($auditorList) {
            $row->auditor = $this->findAuditorByNokso($row->sample_nokso ?? '', $auditorList);
            return $row;
        }, $data);

        return response()->json([
            'success'       => true,
            'data'          => $data,
            'auditor_names' => $auditorNames, // ← INI YANG SEBELUMNYA TIDAK ADA
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    // ── Ambil daftar auditor untuk dropdown modal ──
public function getAuditorList()
{
    $auditors = DB::table('so_all_wh_pic_auditor_db')
        ->select('nama')
        ->whereNotNull('nama')
        ->where('nama', '!=', '')
        ->distinct()
        ->orderBy('nama', 'asc')
        ->get();

    return response()->json(['data' => $auditors]);
}

// ── Sudah ada tapi perlu ditambah findAuditorByNokso ──
private function findAuditorByNokso(string $nokso, $auditorList): string
{
    $nokso = trim($nokso);

    // ── FORMAT 1: G2F0101, G3G0401 (format Gedung BPW via prefix G) ──
    if (preg_match('/^G(\d+)([A-Z]+)(\d{2})\d{2}$/', $nokso, $m)) {
        $gedung = 'BPW0' . $m[1];
        $letter = $m[2];
        $number = (int) $m[3];

        foreach ($auditorList as $aud) {
            if (trim($aud->gedung) !== $gedung) continue;
            if (!preg_match('/^([A-Z]+)(\d+)-[A-Z]+(\d+)$/', trim($aud->lot), $r)) continue;
            if ($r[1] === $letter && $number >= (int)$r[2] && $number <= (int)$r[3]) {
                return trim($aud->nama);
            }
        }

        return '-';
    }

    // ── FORMAT 2: A01A002 → BPW01, letter=A, number=2 ──
    // Struktur: [PrefixHuruf][GedungNum2digit][Letter][NomorLot3digit]
    if (preg_match('/^[A-Z](\d{2})([A-Z])(\d{3})$/', $nokso, $m)) {
        $gedung = 'BPW0' . ltrim($m[1], '0'); // "01" → "BPW01"
        $letter = $m[2];                        // "A"
        $number = (int) $m[3];                  // "002" → 2

        foreach ($auditorList as $aud) {
            if (trim($aud->gedung) !== $gedung) continue;
            if (!preg_match('/^([A-Z]+)(\d+)-[A-Z]+(\d+)$/', trim($aud->lot), $r)) continue;
            if ($r[1] === $letter && $number >= (int)$r[2] && $number <= (int)$r[3]) {
                return trim($aud->nama);
            }
        }

        return '-';
    }

    return '-';
}

}
