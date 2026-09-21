<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Appkso extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil List Project SO untuk Dropdown
        $list_kso = DB::connection('mysql_second')
            ->table('ms_kso')
            ->select('so_name', 'def_counter')
            ->orderBy('recid', 'desc')
            ->get();

        $selected_so = $request->so_name ?? ($list_kso->first()->so_name ?? null);
        session(['so_name' => $selected_so]);

        // 2. Olah Summary (mysql_second)
        $query_summary = DB::connection('mysql_second')->table('cntso');
        if ($selected_so) {
            $query_summary->where('so_name', $selected_so);
        }

        $summary_raw = (clone $query_summary)
            ->select(
                DB::raw('COUNT(recid) as total_scan'),
                DB::raw('SUM(QtyStk) as total_pcs'),
                DB::raw('COUNT(DISTINCT ItemCode) as unique_items'),
                DB::raw('COUNT(CASE WHEN status = "APPROVE" THEN 1 END) as approved')
            )->first();

        $summary = [
            'total_scan'   => $summary_raw->total_scan ?? 0,
            'total_pcs'    => $summary_raw->total_pcs ?? 0,
            'unique_items' => $summary_raw->unique_items ?? 0,
            'approved'     => $summary_raw->approved ?? 0,
        ];

        // 3. TARIK DATA AKTIVITAS (Tabel Kiri)
        $db_bcm = 'bcmcfgv1';
        $all_activities_raw = DB::connection('mysql_second')
            ->table('cntso as c')
            ->leftJoin($db_bcm . '.oprbld as o', 'c.opr', '=', 'o.oprcode')
            ->select('c.ydate_shift', 'c.opr', 'o.oprname', 'c.NoDoc', 'c.ItemCode', 'c.QtyStk', 'c.status', 'c.txndate')
            ->when($selected_so, function ($q) use ($selected_so) {
                return $q->where('c.so_name', $selected_so);
            })
            ->orderBy('c.recid', 'desc')
            ->get();

        // 4. MAPPING DATA UNTUK SISI KANAN & DESKRIPSI (Cross-Server Mapping)
        $master_map = DB::connection('mysql')
            ->table('master_items')
            ->select('item_code_desc', 'description', 'pattern')
            ->get()
            ->keyBy('item_code_desc');

        $activities = $all_activities_raw->map(function ($item) use ($master_map) {
            $master = $master_map->get($item->ItemCode);
            $item->description = $master->description ?? 'N/A di Master';
            $item->pattern = $master->pattern ?? 'UNIDENTIFIED';
            return $item;
        });

        $pic_nokso_map = $all_activities_raw
            ->groupBy('opr')
            ->map(function ($items) {
                return $items->pluck('NoDoc')->unique()->values();
            });

        $pic_list = $all_activities_raw
            ->pluck('opr')
            ->unique()
            ->values();

        // Grouping Sisi Kanan: Resume OE vs OK
        $resume_oe = $activities->filter(fn($i) => str_contains(strtoupper($i->pattern), 'OE'))
            ->groupBy('pattern')->map(fn($items, $pattern) => [
                'pattern' => $pattern,
                'total_qty' => $items->sum('QtyStk'),
                'total_sku' => $items->unique('ItemCode')->count(),
            ])->sortByDesc('total_qty')->values();

        $resume_ok = $activities->filter(fn($i) => str_contains(strtoupper($i->pattern), 'OK'))
            ->groupBy('pattern')->map(fn($items, $pattern) => [
                'pattern' => $pattern,
                'total_qty' => $items->sum('QtyStk'),
                'total_sku' => $items->unique('ItemCode')->count(),
            ])->sortByDesc('total_qty')->values();

        // =========================================================
        // TAMBAHAN: Hitung Resume PIC Stock (SKU & QTY)
        // =========================================================
        $resume_pic = $activities->groupBy('opr')->map(function ($items, $opr) {
            return (object)[
                'opr' => $opr,
                'oprname' => $items->first()->oprname ?? 'Unknown',
                'total_kso' => $items->count(),
                'total_sku' => $items->unique('ItemCode')->count(),
                'total_qty' => $items->sum('QtyStk'),
            ];
        })->sortByDesc('total_sku')->values();
        // =========================================================

        // =========================================================
        // TAMBAHKAN KODE INI: Menghitung KSO & PCS per Gedung
        // =========================================================
        $resume_gedung = [
            'BPW 1' => ['kso' => 0, 'pcs' => 0],
            'BPW 2' => ['kso' => 0, 'pcs' => 0],
            'BPW 3' => ['kso' => 0, 'pcs' => 0],
        ];

        foreach ($activities as $act) {
            $noDoc = trim($act->NoDoc ?? '');
            $prefix = substr($noDoc, 0, 2);

            if ($prefix === 'G1') {
                $resume_gedung['BPW 1']['kso']++;
                $resume_gedung['BPW 1']['pcs'] += $act->QtyStk;
            } elseif ($prefix === 'G2') {
                $resume_gedung['BPW 2']['kso']++;
                $resume_gedung['BPW 2']['pcs'] += $act->QtyStk;
            } elseif ($prefix === 'G3') {
                $resume_gedung['BPW 3']['kso']++;
                $resume_gedung['BPW 3']['pcs'] += $act->QtyStk;
            }
        }
        // =========================================================

        // 5. Productivity (Top 10 Operator)
        $productivity = DB::connection('mysql_second')
            ->table('cntso')
            ->select('opr', DB::raw('COUNT(recid) as total_scan'))
            ->when($selected_so, function ($q) use ($selected_so) {
                return $q->where('so_name', $selected_so);
            })
            ->groupBy('opr')
            ->orderBy('total_scan', 'desc')
            ->limit(10)
            ->get();

        // Kirim semua variabel ke view
        $auditorList = DB::connection('mysql')
            ->table('so_all_wh_pic_auditor_db')
            ->where('warehouse', 'BPW')
            ->select('nama', 'gedung', 'lot')
            ->get();

        $cachedDetail = $activities->map(function ($a) use ($auditorList) {
            return [
                'opr'          => $a->opr,
                'oprname'      => $a->oprname,
                'auditor_nama' => $this->findAuditorByNokso($a->NoDoc ?? '', $auditorList),
                'nokso'        => $a->NoDoc,
                'item'         => $a->ItemCode,
                'deskripsi'    => $a->description,
                'qty'          => $a->QtyStk,
            ];
        })->values();

        return view('appkso.appkso', compact(
            'summary',
            'activities',
            'list_kso',
            'selected_so',
            'productivity',
            'resume_oe',
            'resume_ok',
            'resume_gedung',
            'pic_nokso_map',
            'pic_list',
            'resume_pic',
            'cachedDetail'
        ));
    }
    public function generatePrintPreview(Request $request)
    {
        try {
            $pic = trim($request->pic_name);
            $docFrom = $request->doc_from;
            $docTo = $request->doc_to;
            $tanggalSo = $request->tanggal_so ?? null;
            $soName = $request->so_name ?? session('so_name');

            // Format tanggal: 2025-12-22 → 22 / DESEMBER / 2025
            $bulanNama = [
                1 => 'JANUARI',
                2 => 'FEBRUARI',
                3 => 'MARET',
                4 => 'APRIL',
                5 => 'MEI',
                6 => 'JUNI',
                7 => 'JULI',
                8 => 'AGUSTUS',
                9 => 'SEPTEMBER',
                10 => 'OKTOBER',
                11 => 'NOVEMBER',
                12 => 'DESEMBER'
            ];

            $displayTanggal = '-';
            if ($tanggalSo) {
                $dt = \Carbon\Carbon::parse($tanggalSo);
                $displayTanggal = $dt->day . ' / ' . $bulanNama[$dt->month] . ' / ' . $dt->year;
            }

            $db_bcm = 'bcmcfgv1';

            // Tarik data aktivitas sesuai filter
$soName = $request->so_name ?? session('so_name');

            $rows = DB::connection('mysql_second')
    ->table('cntso as c')
    ->leftJoin($db_bcm . '.oprbld as o', 'c.opr', '=', 'o.oprcode')
    ->select(
        'c.NoDoc as nokso',
        'c.ItemCode as item',
        'c.QtyStk as qty',
        'o.oprname'
    )
    ->where(DB::raw('TRIM(c.opr)'), $pic)
    ->when($soName, function ($q) use ($soName) {   // ← TAMBAH INI
        $q->where('c.so_name', $soName);
    })
    ->whereBetween('c.NoDoc', [$docFrom, $docTo])
    ->orderBy('c.NoDoc', 'asc')
    ->get();

            if ($rows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Data tidak ditemukan untuk PIC: $pic"
                ]);
            }

            // Mapping deskripsi dari master_items (mysql)
            $itemCodes = $rows->pluck('item')->unique()->toArray();
            $master_map = DB::connection('mysql')
                ->table('master_items')
                ->select('item_code_desc', 'description')
                ->whereIn('item_code_desc', $itemCodes)
                ->get()
                ->keyBy('item_code_desc');

            // Satukan deskripsi ke dalam rows
            $rows = $rows->map(function ($row) use ($master_map) {
                $master = $master_map->get($row->item);
                $row->deskripsi = $master->description ?? 'N/A di Master';
                return $row;
            });

            // Ambil list auditor
            $auditorList = DB::connection('mysql')
                ->table('so_all_wh_pic_auditor_db')
                ->where('warehouse', 'BPW')
                ->select('nama', 'gedung', 'lot')
                ->get();

            // Inject auditor per nokso
            $rows = $rows->map(function ($row) use ($auditorList) {
                $row->auditor_nama = $this->findAuditorByNokso($row->nokso ?? '', $auditorList);
                return $row;
            });


            // Tarik ulang map dokumen untuk validasi dropdown di form blade lamamu
            $all_activities_raw = DB::connection('mysql_second')->table('cntso')->get();

            $picNoksoMap = $all_activities_raw
                ->groupBy('opr')
                ->map(fn($items) => $items->pluck('NoDoc')->unique()->values());

            $pics = $all_activities_raw->pluck('opr')->unique()->map(fn($opr) => [
                'value' => $opr,
                'text' => $opr
            ])->values();

            $html = view('appkso.tag_kso', [
                'rows'         => $rows,
                'pics'         => $pics,
                'selectedPIC'  => $pic,
                'docFrom'      => $docFrom,
                'docTo'        => $docTo,
                'picNoksoMap'  => $picNoksoMap,
                'tanggalSo'    => $displayTanggal, // tambah ini
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Line ' . $e->getLine() . ': ' . $e->getMessage()
            ], 500);
        }
    }
    private function buildHtmlTemplate($rows, $settings)
    {
        $layoutOption = $settings['layout'] ?? 'both';

        $html_content = '';

        foreach ($rows as $t) {
            $qty_str = (string) $t['QtyStk'];

            $get_digit = function ($qty_str, $index_from_end) {
                $length = strlen($qty_str);
                $target_index = $length - $index_from_end;

                return ($target_index >= 0)
                    ? (int) $qty_str[$target_index]
                    : null;
            };

            $puluhan_ribu_digit = $get_digit($qty_str, 5);
            $ribuan_digit        = $get_digit($qty_str, 4);
            $ratusan_digit       = $get_digit($qty_str, 3);
            $puluhan_digit       = $get_digit($qty_str, 2);
            $satuan_digit        = $get_digit($qty_str, 1);

            $grade = substr($t['item'], -1) === '0' ? 'OE' : 'OK';

            $sections = [];

            if ($layoutOption == 'gudang' || $layoutOption == 'both') {
                $sections[] = [
                    'label' => 'LEMBAR UNTUK GUDANG',
                    'show_gunting' => ($layoutOption == 'both'),
                    'show_barcode' => true
                ];
            }

            if ($layoutOption == 'arsip' || $layoutOption == 'both') {
                $sections[] = [
                    'label' => 'LEMBAR UNTUK ARSIP',
                    'show_gunting' => false,
                    'show_barcode' => false
                ];
            }

            // PERBAIKAN: Bungkus per item data dengan wrapper page-break yang bersih
            $html_content .= '<div class="page-break"><table class="kartu-table">';

            foreach ($sections as $sec) {
                $html_content .= '
                <tr>
                    <td colspan="6" class="header-area">
                        <div class="title">KARTU STOCK OPNAME</div>
                        <div class="tanggal">TANGGAL : 22 / JUNI / 2026</div>
                        <div class="grade">' . $grade . '</div>
                        ' . ($sec['show_barcode'] ? '
                        <div class="barcode-box">
                            <div class="barcode-title">KODE DOKUMEN & NO. DOC</div>
                            <div class="barcode">*' . $t['nokso'] . '*</div>
                            <div class="barcode-text">' . $t['nokso'] . '</div>
                        </div>' : '') . '
                    </td>
                </tr>
                <tr>
                    <td colspan="4" class="left-text">PT GAJAH TUNGGAL Tbk</td>
                    <td class="right-label">BARANG MILIK PLANT</td>
                    <td class="right-value"><span>: <b>B</b></span></td>
                </tr>
                <tr>
                    <td colspan="4" class="desc"><b>' . $t['deskripsi'] . '</b></td>
                    <td class="right-label">NO. DOCUMENT</td>
                    <td class="right-value"><span>: <b>' . $t['nokso'] . '</b></span></td>
                </tr>
                <tr>
                   <td colspan="4" class="item-barcode">' . ($sec['show_barcode'] ? '*' . $t['item'] . '*' : '') . '</td>
                    <td class="right-label top">NO. INDEX</td>
                    <td class="right-value"><span>:</span></td>
                </tr>
                <tr>
                    <td colspan="4" class="item-text">' . $t['item'] . '</td>
                    <td colspan="2" class="line-number">LINE NUMBER</td>
                </tr>



                <tr class="section-line">
                    <td colspan="4"></td>
                    <td colspan="2" class="section-label">' . $sec['label'] . '</td>
                </tr>
                <tr>
                    <td colspan="2" class="ttd-title">DIHITUNG OLEH</td>
                    <td colspan="2"></td>
                    <td colspan="2" class="ttd-title">DIPERIKSA OLEH</td>
                </tr>
                <tr>
                    <td colspan="2" class="ttd-space"><b>' . $t['oprname'] . '</b></td>
                    <td colspan="2"></td>
                    <td colspan="2" class="ttd-space"><b>..........</b></td>
                </tr>
                <tr>
                    <td colspan="2" class="ttd-line"><div>GUDANG BAN</div></td>
                    <td colspan="2"></td>
                    <td colspan="2" class="ttd-line"><div>TEAM S.O./AUDITOR</div></td>
                </tr>
                ' . ($sec['show_gunting'] ? '
                <tr>
                    <td colspan="6" class="gunting">
                        <img style="width:100%; height:20px;" src="' . asset('images/garis_gunting.png') . '">
                    </td>
                </tr>' : '');
            }

            $html_content .= '</table></div>';
        }

        return '
        <html>
        <head>
            <title>Print Preview Tag KSO</title>
            <style>
                @font-face {
                    font-family: "Libre Barcode 39";
                    src: url("' . asset('fonts/LibreBarcode39-Regular.ttf') . '") format("truetype");
                }
                body {
                    margin:0; padding:0; background:#fff; font-family:"Times New Roman", serif;
                }

                /* PERBAIKAN: Jangan gunakan height: 100vh atau 100% saat print */
                .page-break {
                    page-break-after: always;
                    break-after: page;
                    padding: 0;
                    margin: 0;
                }

                /* PERBAIKAN: Hapus height: 100% agar tabel mengalir alami sesuai konten */
                .kartu-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                    margin-bottom: 20mm; /* Jaga jarak antar kartu jika tercetak */
                }
                .header-area { text-align:center; position:relative; }
                .title { font-size:16px; font-weight:bold; }
                .tanggal { font-size:12px; margin-top:-2px; }
                .grade { font-size:18px; font-weight:bold; margin-top:5px; margin-bottom:5px; }
                .barcode-box { position:absolute; top:0; right:0; text-align:center; }
                .barcode-title { font-size:10px; }
                .barcode { font-family:"Libre Barcode 39"; font-size:35px; line-height:1; font-weight:normal; }
                .barcode-text { font-size:13px; margin-top:-2px; }
                .left-text { padding-left:5px; }
                .desc { padding-left:5px; font-size:18px; font-weight:bold; }
                .item-barcode { font-family:"Libre Barcode 39"; font-size:50px; padding-left:5px; line-height:1; }
                .item-text { padding-left:5px; font-size:10px; font-weight:bold; }
                .right-label { white-space:nowrap; font-size: 11px; }
                .right-value span { display:inline-block; width: 100px; border-bottom:1px solid #000; }
                .line-number { text-align:center; font-weight:bold; }
                .digit-row td { vertical-align:middle; }
                .digit-container { display:flex; justify-content:space-between; padding-right:20px; }
                .digit { width:14px; height:14px; text-align:center; position:relative; font-size:12px; line-height:14px; font-weight:700; -webkit-text-stroke: 0.3px #000; }
                .dot { width:10px; height:10px; border:2px solid #000; border-radius:50%; background:transparent; position:absolute; left:50%; top:50%; transform:translate(-50%, -50%); -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .qty-barcode { font-family:"Libre Barcode 39"; font-size:27px; margin-left:8px; position:relative; top:3px; }
                .section-line { border-bottom:2px solid #000; }
                .section-label { text-align:center; padding-top:5px; }
                .ttd-title { text-align:center; padding-top:10px; }
                .ttd-space { height:60px; vertical-align:bottom; text-align:center; } /* Sedikit dikurangi agar pas selembar */
                .ttd-line div { width:60%; margin:0 auto; border-top:2px solid #000; text-align:center; }
                .gunting img { width:100%; height:50px; margin-top:14px; }

                /* PERBAIKAN: Aturan CSS Print khusus */
                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 10mm 10mm;
                    }
                    html, body {
                        overflow: visible !important;
                        height: auto !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    .page-break {
                        page-break-after: always;
                        break-after: page;
                    }
                    .dot { background:#000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>

            <script>
                document.addEventListener("keydown", function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === "p") {
                        e.preventDefault();
                        alert("Metode Fitur print Seperti Ini tidak diizinkan,\\nGunakan Tombol Print Preview Pada Halaman WEB.");
                    }
                });
            </script>
        </head>
        <body>
            ' . $html_content . '
        </body>
        </html>';
    }
    public function rekapKso(Request $request)
    {
        $selectedSo = $request->so_name ?? session('so_name');
        $selectedPIC = $request->pic_name;
        $selectedAuditor = $request->auditor ?? null;

        // 1. QUERY MASTER UNTUK DROPDOWN & MAPPING (Ambil data berdasarkan SO_NAME saja, jangan di-filter PIC dulu!)
        $masterActivities = DB::connection('mysql_second')
            ->table('cntso as c')
            ->leftJoin('bcmcfgv1.oprbld as o', 'c.opr', '=', 'o.oprcode')
            ->select('c.opr', 'o.oprname', 'c.ItemCode')
            ->when($selectedSo, function ($q) use ($selectedSo) {
                $q->where('c.so_name', $selectedSo);
            })
            ->get();

        // Ambil semua daftar list PIC unik untuk dropdown agar tidak hilang saat dipilih
        $pics = $masterActivities->pluck('opr')->unique()->values();
        $pic_map = $masterActivities->keyBy('opr');



        // 2. QUERY UTAMA UNTUK ISI TABEL REKAP (Baru di-filter pakai PIC jika ada)
        $activitiesQuery = DB::connection('mysql_second')
            ->table('cntso as c')
            ->leftJoin('bcmcfgv1.oprbld as o', 'c.opr', '=', 'o.oprcode')
            ->select(
                'c.ydate_shift',
                'c.opr',
                'o.oprname',
                'c.NoDoc as nokso',
                'c.ItemCode as item',
                'c.QtyStk'
            )
            ->when($selectedSo, function ($q) use ($selectedSo) {
                $q->where('c.so_name', $selectedSo);
            })
            // Filter PIC baru ditaruh di sini untuk data baris tabel saja
            ->when($selectedPIC, function ($q) use ($selectedPIC) {
                $q->where('c.opr', $selectedPIC);
            })
            ->orderBy('c.NoDoc', 'asc');

        // Kalau belum pilih PIC, jangan load data dulu
        if (!$selectedPIC) {
            return view('appkso.rekap_kso', [
                'rows'            => collect(),
                'pics'            => $masterActivities->pluck('opr')->unique()->values(),
                'auditor'         => collect([]),
                'selectedPIC'     => null,
                'selectedAuditor' => null,
                'pic_map'         => $masterActivities->keyBy('opr'),
            ]);
        }

        $activities = $activitiesQuery->get();

        // 3. MAPPING DESKRIPSI BARANG
        $itemCodes = $activities->pluck('item')->unique()->toArray();

        $master_map = DB::connection('mysql')
            ->table('master_items')
            ->select('item_code_desc', 'description')
            ->whereIn('item_code_desc', $itemCodes)
            ->get()
            ->keyBy('item_code_desc');

        // Mapping deskripsi ke baris tabel ($rows) + Amankan nama operator
        $rows = $activities->map(function ($row) use ($master_map, $pic_map) {
            $row->deskripsi = $master_map[$row->item]->description ?? 'N/A di Master';

            // Jika oprname di row kosong, ambil dari map masterActivities
            if (empty($row->oprname)) {
                $row->oprname = $pic_map[$row->opr]->oprname ?? $row->opr;
            }
            return $row;
        });


        // Ambil list auditor
        $auditorList = DB::connection('mysql')
            ->table('so_all_wh_pic_auditor_db')
            ->where('warehouse', 'BPW')
            ->select('nama', 'gedung', 'lot')
            ->get();

        // Inject auditor per nokso
        $rows = $rows->map(function ($row) use ($auditorList) {
            $row->auditor_nama = $this->findAuditorByNokso($row->nokso ?? '', $auditorList);
            return $row;
        });

        // Sementara auditor dikosongkan sesuai bawaan code lama lu
        $auditor = $rows->pluck('auditor_nama')
            ->filter(fn($a) => $a && $a !== '-')
            ->unique()
            ->values();

        return view('appkso.rekap_kso', compact(
            'rows',
            'pics',
            'auditor',
            'selectedPIC',
            'selectedAuditor',
            'pic_map'
        ));

        // Ambil filter dari request
        $selectedPIC = $request->pic_name;
        $selectedAuditor = $request->auditor;

        // Query data jika ada filter, jika tidak ada bisa kosong atau ambil semua
        // Tergantung kebutuhan, kita ambil sesuai filter
        $query = DB::connection('mysql_second')->table('cntso');

        if ($selectedPIC) {
            $query->where('opr', $selectedPIC);
        }
        if ($selectedAuditor) {
            $query->where('auditor', $selectedAuditor);
        }

        // Cek jika filter diisi, barulah tarik data. Jika baru awal buka, biarkan kosong.
        $rows = ($selectedPIC || $selectedAuditor) ? $query->get() : collect();

        // Map deskripsi dari master_items (mysql)
        if ($rows->isNotEmpty()) {
            $itemCodes = $rows->pluck('ItemCode')->unique()->toArray();
            $master_map = DB::connection('mysql')
                ->table('master_items')
                ->select('item_code_desc', 'description')
                ->whereIn('item_code_desc', $itemCodes)
                ->get()
                ->keyBy('item_code_desc');

            $rows = $rows->map(function ($row) use ($master_map) {
                // Samakan properti dengan generatePrintPreview agar blade rekap_kso jalan
                $master = $master_map->get($row->ItemCode);
                $row->item = $row->ItemCode;
                $row->nokso = $row->NoDoc;
                $row->qty = $row->QtyStk;
                $row->deskripsi = $master->description ?? 'N/A di Master';
                return $row;
            });
        }

        return view('appkso.rekap_kso', [
            'pics' => $pics,
            'pic_list' => $pics,
            'pic_nokso_map' => $pic_nokso_map,
            'rows' => $rows,
            'selectedPIC' => $selectedPIC,
            'selectedAuditor' => $selectedAuditor,
            'auditor' => $auditor,
        ]);
    }
    public function generateRekapPreview(Request $request)
    {
        try {
            $pic = $request->pic_name;
            $auditor = $request->auditor;

            $query = DB::connection('mysql_second')
                ->table('cntso');

            if ($pic) {
                $query->where('opr', $pic);
            }

            if ($auditor) {
                $query->where('auditor', $auditor);
            }

            $rows = $query->get();

            if ($rows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data kosong'
                ]);
            }

            $pics = DB::connection('mysql_second')
                ->table('cntso')
                ->pluck('opr')
                ->unique()
                ->values();

            $pic_nokso_map = DB::connection('mysql_second')
                ->table('cntso')
                ->get()
                ->groupBy('opr')
                ->map(fn($i) => $i->pluck('NoDoc')->unique()->values());

            $html = view('appkso.rekap_kso', [
                'rows' => $rows,
                'pics' => $pics,
                'pic_nokso_map' => $pic_nokso_map,
                'selectedPIC' => $pic,
                'selectedAuditor' => $auditor
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function saveSessionDate(Request $request)
    {
        $tgl_so = $request->tgl_so;

        // Simpan tgl SO ke session
        session(['tgl_so' => $tgl_so]);

        // Kembalikan JSON (bukan return back()) agar AJAX blade bisa handle
        return response()->json([
            'success'      => true,
            'tgl_so'       => $tgl_so,
            'tgl_posisi'   => session('tgl_posisi_stock'),
        ]);
    }

    public function saveSessionDatePosisi(Request $request)
    {
        session(['tgl_posisi_stock' => $request->tgl_posisi_stock]);
        return response()->json(['success' => true]);
    }

    private function baseCntsoQuery($selected_so = null)
    {
        return DB::connection('mysql_second')
            ->table('cntso as c')
            ->leftJoin('bcmcfgv1.oprbld as o', 'c.opr', '=', 'o.oprcode')
            ->select(
                'c.ydate_shift',
                'c.opr',
                'o.oprname',
                'c.NoDoc',
                'c.ItemCode',
                'c.QtyStk',
                'c.status',
                'c.txndate',
                'c.so_name'
            )
            ->when($selected_so, function ($q) use ($selected_so) {
                return $q->where('c.so_name', $selected_so);
            });
    }

    private function findAuditorByNokso(string $nokso, $auditorList): string
    {
        // Parse NoDoc: G1B0801 → gedung=BPW01, letter=B, number=8
        if (!preg_match('/^G(\d+)([A-Z]+)(\d{2})\d{2}$/', trim($nokso), $m)) {
            return '-';
        }

        $gedung = 'BPW0' . $m[1];
        $letter = $m[2];
        $number = (int) $m[3];

        foreach ($auditorList as $aud) {
            if (trim($aud->gedung) !== $gedung) continue;

            // Parse lot range: "B01-B65" → letter=B, from=1, to=65
            if (!preg_match('/^([A-Z]+)(\d+)-[A-Z]+(\d+)$/', trim($aud->lot), $r)) continue;

            if ($r[1] === $letter && $number >= (int)$r[2] && $number <= (int)$r[3]) {
                return trim($aud->nama);
            }
        }

        return '-';
    }

    public function generateUploadOracleXlsm(Request $request)
    {
        $opr    = trim($request->opr);
        $soName = trim($request->so_name);

        if (!$opr || !$soName) {
            return response()->json(['success' => false, 'message' => 'Parameter tidak lengkap.'], 400);
        }

        // 1. Ambil data dari DB
        $activities = DB::connection('mysql_second')
            ->table('cntso as c')
            ->leftJoin('bcmcfgv1.oprbld as o', 'c.opr', '=', 'o.oprcode')
            ->select('c.NoDoc', 'c.ItemCode', 'c.QtyStk', 'o.oprname')
            ->where('c.so_name', $soName)
            ->where(DB::raw('TRIM(c.opr)'), $opr)
            ->orderBy('c.NoDoc', 'asc')
            ->get();

        if ($activities->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan untuk operator ini.'], 404);
        }

        $oprName  = $activities->first()->oprname ?? $opr;
        $totalSku = $activities->unique('ItemCode')->count();
        $totalQty = $activities->sum('QtyStk');

        // 2. Cek template
        $templatePath = storage_path('app/templates/upload_oracle_template.xlsm');
        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'File template tidak ditemukan.'], 500);
        }

        // 3. Tentukan path output ke shared folder
        $sharedFolder = 'D:\\00. DATA BARCODE DESKTOP\\00. UPLOAD TAG COUNTS ORACLE';
        $filename     =  strtoupper(trim($oprName)) . '_' . strtoupper(trim($opr)) . '_' . now()->format('Ymd_His') . '.xlsm';
        $outputPath   = $sharedFolder . DIRECTORY_SEPARATOR . $filename;

        // Buat folder jika belum ada
        if (!is_dir($sharedFolder)) {
            mkdir($sharedFolder, 0755, true);
        }

        // 4. Copy template ke shared folder
        if (!copy($templatePath, $outputPath)) {
            return response()->json(['success' => false, 'message' => 'Gagal menyalin template ke shared folder. Cek permission folder.'], 500);
        }

        // 5. Inject data via ZipArchive
        $zip = new \ZipArchive();
        if ($zip->open($outputPath) !== true) {
            return response()->json(['success' => false, 'message' => 'Gagal membuka file template.'], 500);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            return response()->json(['success' => false, 'message' => 'Sheet XML tidak ditemukan dalam template.'], 500);
        }

        // 6. Bangun baris XML
        $rowsXml = '';

        // Info header operator (row 5, 6, 7)
        $rowsXml .= $this->buildXmlRow(5, ['E' => $oprName]);
        $rowsXml .= $this->buildXmlRow(6, ['E' => $totalSku]);
        $rowsXml .= $this->buildXmlRow(7, ['E' => $totalQty]);

        // Header kolom row 12
        $rowsXml .= $this->buildXmlRow(12, [
            'A' => 'SysTab',
            'B' => 'KSO',
            'C' => 'TAB',
            'D' => 'ITEM',
            'E' => 'TAB',
            'F' => 'SUBINV',
            'G' => 'TAB',
            'H' => 'PCS',
            'I' => 'TAB',
            'J' => 'QTY',
            'K' => 'TAB',
            'L' => 'TAB',
            'M' => 'TAB',
            'N' => '*DN',
        ]);

        // Data rows mulai row 13
        $row        = 13;
        $isFirstRow = true;
        foreach ($activities as $act) {
            $rowsXml .= $this->buildXmlRow($row, [
                'A' => $isFirstRow ? 'TAB' : '',
                'B' => $act->NoDoc,
                'C' => 'TAB',
                'D' => $act->ItemCode,
                'E' => 'TAB',
                'F' => 'BPW1',
                'G' => 'TAB',
                'H' => 'PCS',
                'I' => 'TAB',
                'J' => $act->QtyStk,
                'K' => 'TAB',
                'L' => 'TAB',
                'M' => 'TAB',
                'N' => '*DN',
            ]);
            $isFirstRow = false;
            $row++;
        }

        // 7. Replace sheetData di XML
        $newSheetXml = preg_replace(
            '/<sheetData>.*?<\/sheetData>/s',
            '<sheetData>' . $rowsXml . '</sheetData>',
            $sheetXml
        );

        $zip->addFromString('xl/worksheets/sheet1.xml', $newSheetXml);
        $zip->close();

        // 8. Return path UNC untuk ditampilkan ke user
        $uncPath = '\\\\10.129.48.179\\00. DATA BARCODE DESKTOP\\00. UPLOAD TAG COUNTS ORACLE\\' . $filename;

        return response()->json([
            'success'   => true,
            'filename'  => $filename,
            'path'      => $uncPath,
            'folder'    => '\\\\10.129.48.179\\00. DATA BARCODE DESKTOP\\00. UPLOAD TAG COUNTS ORACLE',
            'opr'       => strtoupper($oprName),
            'total_sku' => $totalSku,
            'total_qty' => number_format($totalQty),
        ]);
    }

    // Helper: bangun satu baris XML spreadsheet
    private function buildXmlRow(int $rowNum, array $cells): string
    {
        $colMap = [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
            'H' => 8,
            'I' => 9,
            'J' => 10,
            'K' => 11,
            'L' => 12,
            'M' => 13,
            'N' => 14,
        ];

        $cellsXml = '';
        foreach ($cells as $col => $value) {
            $ref = $col . $rowNum;
            if (is_numeric($value) && $value !== '') {
                $cellsXml .= "<c r=\"{$ref}\"><v>{$value}</v></c>";
            } else {
                $escaped   = htmlspecialchars((string)$value, ENT_XML1);
                $cellsXml .= "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }
        }

        return "<row r=\"{$rowNum}\">{$cellsXml}</row>";
    }
    public function openSharedFolder(Request $request)
    {
        $output = [];
        $returnCode = 0;

        exec('cmd /c start "" "\\\10.129.48.179\00. DATA BARCODE DESKTOP\00. UPLOAD TAG COUNTS ORACLE"', $output, $returnCode);

        return response()->json([
            'success' => true,
            'output' => $output,
            'return_code' => $returnCode
        ]);
    }

    // =========================================================
    // API UNTUK GENERATE DATA EXPORT BA
    // =========================================================
    public function getExportBaData(Request $request)
    {
        $soName = $request->so_name;

        if (!$soName) {
            return response()->json(['success' => false, 'message' => 'SO Name tidak boleh kosong.']);
        }

        try {
            // 1. Tarik Data Counted (cntso)
            $countedData = DB::connection('mysql_second')
                ->table('cntso')
                ->where('so_name', $soName)
                ->select('ItemCode', DB::raw('SUM(QtyStk) as counted_qty'))
                ->groupBy('ItemCode')
                ->get()
                ->keyBy('ItemCode');

            // 2. Tarik Data Onhand (snapshot_bpw)
            $onhandData = DB::connection('mysql')
                ->table('snapshot_bpw')
                ->where('so_name', $soName)
                ->select('ItemCode', DB::raw('SUM(QtyStk) as onhand_qty'))
                ->groupBy('ItemCode')
                ->get()
                ->keyBy('ItemCode');

            // 3. Gabungkan semua ItemCode unik dari kedua tabel
            $allItemCodes = collect($countedData->keys())
                ->merge($onhandData->keys())
                ->unique()
                ->values()
                ->toArray();

            // PROTEKSI: Jika benar-benar kosong, langsung return
            if (empty($allItemCodes)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            // 4. Tarik Deskripsi Master Item
            $masterItems = DB::connection('mysql')
                ->table('master_items')
                ->whereIn('item_code_desc', $allItemCodes)
                ->select('item_code_desc', 'description')
                ->get()
                ->keyBy('item_code_desc');

            // 5. Tarik Master Item Similar (LOGIKA DUA ARAH)
            $similarRecords = DB::connection('mysql')
                ->table('master_item_similar')
                ->whereIn('ItemCode', $allItemCodes)
                ->orWhereIn('ItemCodeSimilar', $allItemCodes)
                ->get();

            $similarMap = [];
            foreach ($similarRecords as $sim) {
                $itemCode = trim($sim->ItemCode);
                $similarCode = trim($sim->ItemCodeSimilar);

                if (in_array($itemCode, $allItemCodes) && in_array($similarCode, $allItemCodes)) {
                    $similarMap[$itemCode][] = $similarCode;
                    $similarMap[$similarCode][] = $itemCode;
                }

                // if (in_array($itemCode, $allItemCodes)) {
                //     $similarMap[$itemCode][] = $similarCode;
                // }
                // if (in_array($similarCode, $allItemCodes)) {
                //     $similarMap[$similarCode][] = $itemCode;
                // }
            }

            // 6. Rangkai Data Final
            $result = [];
            foreach ($allItemCodes as $item) {
                $counted = $countedData->has($item) ? $countedData[$item]->counted_qty : 0;
                $onhand  = $onhandData->has($item) ? $onhandData[$item]->onhand_qty : 0;
                $desc    = $masterItems->has($item) ? $masterItems[$item]->description : '-';

                $similarText = '';

                if (isset($similarMap[$item]) && !empty($similarMap[$item])) {
                    // Pakai array_unique & array_values agar mencegah duplikasi data jika ada relasi dobel di DB
                    $similars = array_values(array_unique($similarMap[$item]));

                    // Filter out item itu sendiri (jaga-jaga kalau di tabel similar ada relasi ke diri sendiri)
                    $similars = array_filter($similars, function ($val) use ($item) {
                        return $val !== $item;
                    });

                    if (count($similars) > 0) {
                        $similarText = 'Size Similar Dengan Item ' . implode(', ', $similars);
                    }
                }

                // if (isset($similarMap[$item])) {
                //     $similars = array_unique($similarMap[$item]);
                //     $similarText = 'Size Similar Dengan Item ' . implode(', ', $similars);
                // }

                // =======================================================
                // LOGIKA BARU: Jika Selisih Fisik 0, kosongkan Penjelasan
                // =======================================================
                $selisihFisik = (int)$counted - (int)$onhand;
                if ($selisihFisik === 0) {
                    $similarText = ''; // Jangan tampilkan data similar
                }
                // =======================================================

                $result[] = [
                    'item'    => $item,
                    'desc'    => $desc,
                    'onhand'  => (int)$onhand,
                    'counted' => (int)$counted,
                    'similar' => $similarText
                ];
            }

            // Urutkan berdasarkan Kode Item A-Z
            usort($result, function ($a, $b) {
                return strcmp($a['item'], $b['item']);
            });

            return response()->json([
                'success' => true,
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}
