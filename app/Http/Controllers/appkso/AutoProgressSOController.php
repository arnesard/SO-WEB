<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoProgressSOController extends Controller
{
    // =========================================================
    // HELPER: Map prefix NoDoc -> Nama Gedung
    // =========================================================
    private $prefixToGedung = [
        'G1' => 'BPW01',
        'G2' => 'BPW02',
        'G3' => 'BPW03',
        'G4' => 'BPW04',
    ];

    // =========================================================
    // INDEX - Halaman Utama Progress SO
    // =========================================================
    public function index(Request $request)
    {
        $selectedGedung = $request->input('gedung');
        $searchAuditor  = $request->input('auditor');
        $soName         = $request->input('so_name');

        // 1. Ambil List Gedung (Untuk Dropdown)
        $gedungs = DB::connection('mysql')
            ->table('so_all_wh_pic_auditor_db')
            ->select('gedung')
            ->whereNotNull('gedung')
            ->distinct()
            ->pluck('gedung');

        // 2. Siapkan Master Auditor & Aturan Mapping
        $auditorsRaw  = DB::connection('mysql')->table('so_all_wh_pic_auditor_db')->get();
        $auditorRules = [];
        $auditorStats = [];
        $pennengToAuditorMap = []; // Mapping no_penneng -> nama_auditor

        $unmappedKey = 'BELUM TER-MAPPING';

        // Inisialisasi SEMUA Auditor agar selalu tampil di Card
        foreach ($auditorsRaw as $aud) {
            $nama = trim($aud->nama);
            $gedung = trim($aud->gedung);
            $noPenneng = trim($aud->no_penneng);

            // Mapping NIK buat nyari Aktual (A)
            if ($noPenneng !== '') {
                $pennengToAuditorMap[$noPenneng] = $nama;
            }

            if (!isset($auditorStats[$nama])) {
                $auditorStats[$nama] = [
                    'auditor'       => $nama,
                    'gedung_list'   => [],
                    'total_data'    => 0,  // Target (B) KSO
                    'verified_data' => 0,  // Aktual (A) KSO
                    'total_qty'     => 0,  // Target (B) PCS
                    'verified_qty'  => 0,  // Aktual (A) PCS
                ];
            }

            if (!empty($gedung) && !in_array($gedung, $auditorStats[$nama]['gedung_list'])) {
                $auditorStats[$nama]['gedung_list'][] = $gedung;
            }

            // Parsing Rentang Lot
            $lotStr = str_replace(' ', '', strtoupper(trim($aud->lot)));
            if (strpos($lotStr, '-') !== false) {
                $range = explode('-', $lotStr);
                $type = 'range';
                $startLot = $range[0];
                $endLot   = isset($range[1]) && $range[1] !== '' ? $range[1] : $startLot;
            } else {
                $startLot = $endLot = '';
                $type = 'list';
                $lots = array_filter(array_map('trim', explode(',', $lotStr)));
            }

            $auditorRules[] = (object)[
                'nama'      => $nama,
                'gedung'    => $gedung,
                'type'      => $type,
                'start_lot' => $startLot,
                'end_lot'   => $endLot,
                'lots'      => $lots ?? [],
            ];
        }

        $auditorStats[$unmappedKey] = [
            'auditor'       => $unmappedKey,
            'gedung_list'   => [],
            'total_data'    => 0,
            'verified_data' => 0,
            'total_qty'     => 0,
            'verified_qty'  => 0,
        ];

        // $soName='SO External Gudang Ban B - 21/06/26';
        // 3. Tarik Data Transaksi cntso
        $cntsoData = DB::connection('fginvc')
            ->table('cntso')
             ->select('NoDoc', 'QtyStk', 'opr_v', 'scantime_v')
            ->when($soName, fn($q) => $q->where('so_name', $soName))
            ->get();

        $cntsoDataKarantina = DB::connection('mysql')
            ->table('so_karantina_scan')
            ->select('NoDoc', 'QtyStk', 'opr_v')
            ->get();

        $dataGabungan = $cntsoData->merge($cntsoDataKarantina);

// Tambah query ini setelah $dataGabungan = $cntsoData->merge($cntsoDataKarantina);
$scanTimeByAuditor = [];

// Dari cntso
foreach ($cntsoData as $row) {
    $oprV = trim((string)$row->opr_v);
    if ($oprV === '' || empty($row->scantime_v)) continue;

    $auditorName = $pennengToAuditorMap[$oprV] ?? null;
    if (!$auditorName) continue;

    if (!isset($scanTimeByAuditor[$auditorName])) {
        $scanTimeByAuditor[$auditorName] = [];
    }
    $scanTimeByAuditor[$auditorName][] = $row->scantime_v;
}

// Dari so_karantina_scan
$karantinaWithTime = DB::connection('mysql')
    ->table('so_karantina_scan')
    ->select('opr_v', 'scantime_v')
    ->whereNotNull('scantime_v')
    ->where('scantime_v', '!=', '')
    ->get();

foreach ($karantinaWithTime as $row) {
    $oprV = trim((string)$row->opr_v);
    if ($oprV === '' || empty($row->scantime_v)) continue;

    $auditorName = $pennengToAuditorMap[$oprV] ?? null;
    if (!$auditorName) continue;

    if (!isset($scanTimeByAuditor[$auditorName])) {
        $scanTimeByAuditor[$auditorName] = [];
    }
    $scanTimeByAuditor[$auditorName][] = $row->scantime_v;
}

// =========================================================
// HITUNG DURASI GLOBAL & PER GEDUNG
// =========================================================
$scanTimeGlobal = [];
$scanTimePerGedung = [];

foreach ($cntsoData as $row) {
    if (empty($row->scantime_v)) continue;

    // Global
    $scanTimeGlobal[] = $row->scantime_v;

    // Per Gedung
    $noDoc = strtoupper(str_replace(' ', '', trim($row->NoDoc)));
    if (strlen($noDoc) < 2) continue;
    $prefix    = substr($noDoc, 0, 2);
    $gedungApp = $this->prefixToGedung[$prefix] ?? null;
    if (!$gedungApp) continue;

    $scanTimePerGedung[$gedungApp][] = $row->scantime_v;
}

// Juga dari karantina
foreach ($karantinaWithTime as $row) {
    if (empty($row->scantime_v)) continue;
    $scanTimeGlobal[] = $row->scantime_v;

    $noDoc = strtoupper(str_replace(' ', '', trim($row->NoDoc ?? '')));
    if (strlen($noDoc) < 2) continue;
    $prefix    = substr($noDoc, 0, 2);
    $gedungApp = $this->prefixToGedung[$prefix] ?? null;
    if (!$gedungApp) continue;
    $scanTimePerGedung[$gedungApp][] = $row->scantime_v;
}

$parseTime = function($timeStr) {
    if (empty($timeStr)) return null;
    $dt = \DateTime::createFromFormat('d/m/Y h:i:s A', $timeStr);
    if ($dt) return $dt;
    $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $timeStr);
    if ($dt) return $dt;
    return null;
};

$calcDuration = function($times) use ($parseTime) {
    $times = array_filter($times);
    if (empty($times)) return null;
    sort($times);
    $start = $parseTime(reset($times));
    $end   = $parseTime(end($times));
    if (!$start || !$end) return null;
    $diff = $start->diff($end);
    return ($diff->h * 60) + $diff->i;
};

$globalDuration = $calcDuration($scanTimeGlobal);

$durasiPerGedung = [];
foreach ($scanTimePerGedung as $gedung => $times) {
    $durasiPerGedung[$gedung] = $calcDuration($times);
}

        // dd($dataGabungan);

        $globalTotal  = ['total_data' => 0, 'verified_data' => 0, 'total_qty' => 0, 'verified_qty' => 0];
        $gedungStats  = [];

        // 4. Proses Iterasi & Mapping (Opsi 1: Strict Area Mapping)
        foreach ($dataGabungan as $row) {
            $noDoc = strtoupper(str_replace(' ', '', trim($row->NoDoc)));
            if (strlen($noDoc) < 5) continue;

            $prefix    = substr($noDoc, 0, 2);
            $gedungApp = $this->prefixToGedung[$prefix] ?? 'UNMAPPED';
            $lotApp    = substr($noDoc, 2, 3);

            $lotLetter = substr($lotApp, 0, 1);
            $lotNumber = (int) substr($lotApp, 1, 2);

            $oprV = trim((string)$row->opr_v);

            // CEK SIAPA YANG VERIFIKASI (Aktual A)
            $verifiedBy = null;
            if ($oprV !== '' && isset($pennengToAuditorMap[$oprV])) {
                $verifiedBy = $pennengToAuditorMap[$oprV];
            }

            // CEK SIAPA PEMILIK TERITORI (Target B)
            $mappedAuditor = $unmappedKey;

            foreach ($auditorRules as $rule) {
                if ($rule->gedung === $gedungApp) {
                    $matched = false;

                    if ($rule->type === 'range') {
                        $startLetter = substr($rule->start_lot, 0, 1);
                        $startNumber = (int) substr($rule->start_lot, 1, 2);

                        $endLetter   = substr($rule->end_lot, 0, 1);
                        $endNumber   = (int) substr($rule->end_lot, 1, 2);

                        if ($lotLetter === $startLetter && $lotLetter === $endLetter) {
                            if ($lotNumber >= $startNumber && $lotNumber <= $endNumber) {
                                $matched = true;
                            }
                        }
                    } else {
                        if (in_array($lotApp, $rule->lots)) {
                            $matched = true;
                        }
                    }

                    if ($matched) {
                        $mappedAuditor = $rule->nama;
                        break;
                    }
                }
            }

            if ($selectedGedung && $gedungApp !== $selectedGedung) continue;

            // --- A. Masukkan ke Total Global ---
            $globalTotal['total_data']++;
            $globalTotal['total_qty'] += $row->QtyStk;
            if ($verifiedBy !== null) {
                $globalTotal['verified_data']++;
                $globalTotal['verified_qty'] += $row->QtyStk;
            }

            // --- B. Masukkan ke Total Breakdown Gedung ---
            if (!isset($gedungStats[$gedungApp])) {
                $gedungStats[$gedungApp] = [
                    'lokasi'        => $gedungApp,
                    'total_data'    => 0,
                    'verified_data' => 0,
                    'total_qty'     => 0,
                    'verified_qty'  => 0,
                ];
            }
            $gedungStats[$gedungApp]['total_data']++;
            $gedungStats[$gedungApp]['total_qty'] += $row->QtyStk;
            if ($verifiedBy !== null) {
                $gedungStats[$gedungApp]['verified_data']++;
                $gedungStats[$gedungApp]['verified_qty'] += $row->QtyStk;
            }

            // --- C. Masukkan ke Keranjang Auditor ---
            // 1. Tambah Target (B) ke Pemilik Teritori
            if (isset($auditorStats[$mappedAuditor])) {
                $auditorStats[$mappedAuditor]['total_data']++;
                $auditorStats[$mappedAuditor]['total_qty'] += $row->QtyStk;
            }

            // 2. Tambah Aktual (A) ke Auditor yang Mengerjakan (Jika ada)
            if ($verifiedBy !== null && isset($auditorStats[$verifiedBy])) {
                $auditorStats[$verifiedBy]['verified_data']++;
                $auditorStats[$verifiedBy]['verified_qty'] += $row->QtyStk;
            }
        }

// 5. Finishing Format & Filter
$finalAuditorsData = [];
foreach ($auditorStats as $key => $stat) {
    if ($key === $unmappedKey && $stat['total_data'] == 0) {
        continue;
    }

    $stat['gedung_label'] = empty($stat['gedung_list']) ? '-' : implode(', ', $stat['gedung_list']);

    // Hitung durasi scan
    $stat['scan_start']    = null;
    $stat['scan_end']      = null;
    $stat['scan_duration'] = null;

    if (!empty($scanTimeByAuditor[$key])) {
        $times = array_filter($scanTimeByAuditor[$key]);
        sort($times);
        $stat['scan_start'] = reset($times);
        $stat['scan_end']   = end($times);

      try {
            $parseTime = function($timeStr) {
                if (empty($timeStr)) return null;
                // Format cntso: "22/06/2026 08:19:55 AM"
                $dt = \DateTime::createFromFormat('d/m/Y h:i:s A', $timeStr);
                if ($dt) return $dt;
                // Format karantina: "2026-06-22 08:24:17"
                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $timeStr);
                if ($dt) return $dt;
                return null;
            };

            $start = $parseTime($stat['scan_start']);
            $end   = $parseTime($stat['scan_end']);

            if ($start && $end) {
                $diff = $start->diff($end);
                $stat['scan_duration'] = ($diff->h * 60) + $diff->i;
            } else {
                $stat['scan_duration'] = null;
            }
        } catch (\Exception $e) {
            $stat['scan_duration'] = null;
        }
    }

    $finalAuditorsData[] = (object)$stat;
}

        $auditorsData = collect($finalAuditorsData);

        if ($searchAuditor) {
            $auditorsData = $auditorsData->filter(function ($item) use ($searchAuditor) {
                return stripos($item->auditor, $searchAuditor) !== false;
            });
        }

        $auditorsData = $auditorsData->sortBy(function ($item) use ($unmappedKey) {
            return $item->auditor === $unmappedKey ? 'ZZZ' : $item->auditor;
        })->values();

        $progressPerGedung = collect($gedungStats)->map(function ($item) {
            $item['progress'] = $item['total_qty'] > 0
                ? number_format(($item['verified_qty'] / $item['total_qty']) * 100, 2, '.', '')
                : 0;
            return (object)$item;
        })->sortBy('lokasi')->values();

        $globalProgress = $globalTotal['total_qty'] > 0
            ? number_format(($globalTotal['verified_qty'] / $globalTotal['total_qty']) * 100, 2, '.', '')
            : 0;

        $listKso = DB::connection('fginvc')
            ->table('cntso')
            ->select('so_name', DB::raw('COUNT(*) as def_counter'))
            ->whereNotNull('so_name')
            ->where('so_name', '!=', '')
            ->groupBy('so_name')
            ->orderBy('so_name')
            ->get();

// dd($auditorsData);

      return view('appkso.auto_progress_so', [
            'gedungs'           => $gedungs,
            'selectedGedung'    => $selectedGedung,
            'searchAuditor'     => $searchAuditor,
            'globalSummary'     => (object)$globalTotal,
            'globalProgress'    => $globalProgress,
            'progressPerGedung' => $progressPerGedung,
            'auditorsData'      => $auditorsData,
            'list_kso'          => $listKso,
            'selected_so'       => $soName,
            'soName'            => $soName,
            'globalDuration'    => $globalDuration,
            'durasiPerGedung'   => $durasiPerGedung,
        ]);
    }

    // =========================================================
    // GET DETAIL - Modal Detail per Auditor
    // =========================================================
    public function getDetail(Request $request)
    {
        $auditorName    = $request->input('auditor');
        $selectedGedung = $request->input('gedung');
        $selectedSo     = $request->input('so_name');

        $unmappedKey = 'BELUM TER-MAPPING';

        // 1. Tarik Aturan Mapping
        $auditorsRaw  = DB::connection('mysql')->table('so_all_wh_pic_auditor_db')->get();
        $auditorRules = [];
        $pennengToAuditorMap = [];

        foreach ($auditorsRaw as $aud) {
            $nama = trim($aud->nama);
            $noPenneng = trim($aud->no_penneng);

            if ($noPenneng !== '') {
                $pennengToAuditorMap[$noPenneng] = $nama;
            }

            $lotStr = str_replace(' ', '', strtoupper(trim($aud->lot)));
            if (strpos($lotStr, '-') !== false) {
                $range = explode('-', $lotStr);
                $type = 'range';
                $startLot = $range[0];
                $endLot   = isset($range[1]) && $range[1] !== '' ? $range[1] : $startLot;
            } else {
                $startLot = $endLot = '';
                $type = 'list';
                $lots = array_filter(array_map('trim', explode(',', $lotStr)));
            }

            $auditorRules[] = (object)[
                'nama'      => $nama,
                'gedung'    => trim($aud->gedung),
                'type'      => $type,
                'start_lot' => $startLot,
                'end_lot'   => $endLot,
                'lots'      => $lots ?? [],
            ];
        }

        // 2. Mapping PIC Stock
        $picStockMap = [];
        $picStockRecords = DB::connection('mysql')->table('so_all_wh_pic_stock_db')->select('no_penneng', 'nama')->get();
        foreach ($picStockRecords as $pic) {
            if (!empty($pic->no_penneng)) {
                $picStockMap[trim($pic->no_penneng)] = trim($pic->nama);
            }
        }

        // 3. Tarik Data cntso
        $cntsoData = DB::connection('fginvc')
            ->table('cntso')
            ->select('NoDoc', 'ItemCode', 'QtyStk', 'opr', 'opr_v', 'so_name', 'cat')
            ->when($selectedSo, fn($q) => $q->where('so_name', $selectedSo))
            ->get();

        $cntsoDataKarantina = DB::connection('mysql')
            ->table('so_karantina_scan')
            ->select('NoDoc',DB::raw('item_code_desc as ItemCode'), 'QtyStk', 'opr', 'opr_v')
            ->get();

        $dataGabungan = $cntsoData->merge($cntsoDataKarantina);

        // 4. Lookup description dari master_items
        $itemCodes = $dataGabungan->pluck('ItemCode')->filter()->map(fn($val) => trim((string)$val))->unique()->values()->toArray();
        $masterItems = [];
        if (!empty($itemCodes)) {
            $masterRecords = DB::connection('mysql')->table('master_items')->whereIn('item_code_desc', $itemCodes)->select('item_code_desc', 'description')->get();
            foreach ($masterRecords as $master) {
                $desc = trim((string)$master->description);
                $key = strtoupper(trim((string)$master->item_code_desc));
                $masterItems[$key] = $desc !== '' ? $desc : '-';
            }
        }

        $filteredData = collect();
        $picStats     = [];
        $totalKso = $verifiedKso = $totalPcs = $verifiedPcs = 0;

        foreach ($dataGabungan as $row) {
            $noDoc = strtoupper(str_replace(' ', '', trim($row->NoDoc)));
            if (strlen($noDoc) < 5) continue;

            $prefix    = substr($noDoc, 0, 2);
            $gedungApp = $this->prefixToGedung[$prefix] ?? 'UNMAPPED';
            $lotApp    = substr($noDoc, 2, 3);

            $lotLetter = substr($lotApp, 0, 1);
            $lotNumber = (int) substr($lotApp, 1, 2);

            if ($selectedGedung && $gedungApp !== $selectedGedung) continue;

            $oprV = trim((string)$row->opr_v);

            // CEK SIAPA YANG VERIFIKASI
            $verifiedBy = null;
            if ($oprV !== '' && isset($pennengToAuditorMap[$oprV])) {
                $verifiedBy = $pennengToAuditorMap[$oprV];
            }

            // KUNCI SINKRONISASI: Cari pemilik area target
            $mappedAuditor = $unmappedKey;
            foreach ($auditorRules as $rule) {
                if ($rule->gedung === $gedungApp) {
                    $matched = false;

                    if ($rule->type === 'range') {
                        $startLetter = substr($rule->start_lot, 0, 1);
                        $startNumber = (int) substr($rule->start_lot, 1, 2);

                        $endLetter   = substr($rule->end_lot, 0, 1);
                        $endNumber   = (int) substr($rule->end_lot, 1, 2);

                        if ($lotLetter === $startLetter && $lotLetter === $endLetter) {
                            if ($lotNumber >= $startNumber && $lotNumber <= $endNumber) {
                                $matched = true;
                            }
                        }
                    } else {
                        if (in_array($lotApp, $rule->lots)) {
                            $matched = true;
                        }
                    }

                    if ($matched) {
                        $mappedAuditor = $rule->nama;
                        break;
                    }
                }
            }

            // FILTER: Tampilkan di Modal jika Auditor ini adalah Pemilik Teritori (Target)
            // ATAU dia yang memverifikasi data ini (Aktual)
            if ($mappedAuditor !== $auditorName && $verifiedBy !== $auditorName) {
                continue;
            }

            $picName = 'Unknown PIC';
            $rowOpr = trim((string)$row->opr);
            if ($rowOpr !== '') {
                $picName = ucwords(strtolower($picStockMap[$rowOpr] ?? $rowOpr));
            }

            // Hitung Statistik Modal HANYA jika data ini milik teritorinya (Target B)
            // Atau jika bukan teritorinya tapi dia yg ngerjain (Nge-back-up)
            if (!isset($picStats[$picName])) {
                $picStats[$picName] = ['total_kso' => 0, 'verified_kso' => 0, 'total_pcs' => 0, 'verified_pcs' => 0];
            }

            // Nambah Target (B) jika ini teritorinya
            if ($mappedAuditor === $auditorName) {
                $picStats[$picName]['total_kso']++;
                $picStats[$picName]['total_pcs'] += $row->QtyStk;
                $totalKso++;
                $totalPcs += $row->QtyStk;
            }

            // Nambah Aktual (A) jika ini yang dia kerjain
            if ($verifiedBy === $auditorName) {
                $picStats[$picName]['verified_kso']++;
                $picStats[$picName]['verified_pcs'] += $row->QtyStk;
                $verifiedKso++;
                $verifiedPcs += $row->QtyStk;
            }

            $itemKey = strtoupper(trim((string)$row->ItemCode));

            $filteredData->push((object)[
                'gedung'    => $gedungApp,
                'nokso'     => $row->NoDoc,
                'pic_stock' => htmlspecialchars($picName, ENT_QUOTES),
                'auditor'   => $mappedAuditor, // Tampilkan siapa pemilik aslinya
                'item'      => $row->ItemCode,
                'deskripsi' => $masterItems[$itemKey] ?? 'TIDAK ADA DI MASTER',
                'qty'       => $row->QtyStk,
                'status'    => ($verifiedBy !== null) ? 'Sudah' : 'Belum',
            ]);
        }

        // 6. Build HTML Tabel
        $html = '';
        foreach ($filteredData as $index => $row) {
            $statusBadge = $row->status === 'Sudah'
                ? "<span class='badge bg-success' style='font-size:9px;'><i data-lucide='check-circle' style='width:10px;height:10px;'></i> Sudah Verifikasi</span>"
                : "<span class='badge bg-warning text-dark' style='font-size:9px;'><i data-lucide='loader-2' style='width:10px;height:10px;'></i> Belum Verifikasi</span>";

            $descText = $row->deskripsi === 'TIDAK ADA DI MASTER'
                ? "<small class='text-danger fst-italic'>{$row->deskripsi}</small>"
                : "<small>{$row->deskripsi}</small>";

            $html .= "<tr style='border-bottom:1px solid rgba(255,255,255,0.1);' class='detail-row' data-status='{$row->status}' data-pic='{$row->pic_stock}'>
                <td class='text-center'>" . ($index + 1) . "</td>
                <td class='text-center text-info fw-bold'>{$row->gedung}</td>
                <td>{$row->nokso}</td>
                <td>{$row->pic_stock}</td>
                <td class='text-warning'>{$row->auditor}</td>
                <td>{$row->item}</td>
                <td>{$descText}</td>
                <td class='text-center text-success fw-bold'>" . number_format($row->qty) . "</td>
                <td class='text-center'>{$statusBadge}</td>
            </tr>";
        }

        if ($filteredData->isEmpty()) {
            $html = "<tr><td colspan='9' class='text-center text-muted py-4'>Tidak ada data scan untuk auditor ini.</td></tr>";
        }

        // 7. Build Matrix Summary HTML
        $theadHtml  = "<tr><th style='width:70px;text-align:center;color:#000;font-size:10px;font-weight:bold;letter-spacing:1px;'>METRIK</th>";
        $ksoRowHtml = "<tr><td class='text-center border-start border-secondary py-2' style='color:#000;font-weight:bold;'>KSO</td>";
        $pcsRowHtml = "<tr><td class='text-center border-start border-secondary py-2' style='color:#000;font-weight:bold;'>PCS</td>";

        foreach ($picStats as $name => $stats) {
            $theadHtml  .= "<th class='text-center pb-2 border-start' style='min-width:140px;color:#000;font-size:11px;'>{$name}</th>";
            $ksoRowHtml .= "<td class='text-center border-start border-info py-2' style='background:rgba(0,246,255,0.05);'>
                <div class='box-stat p-1 mx-1'>
                    <span class='text-success fw-bold' style='font-size:13px;'>" . number_format($stats['verified_kso']) . "</span>
                    <span class='text-secondary' style='font-size:9px;'>/ " . number_format($stats['total_kso']) . "</span>
                </div></td>";
            $pcsRowHtml .= "<td class='text-center border-start border-info py-2' style='background:rgba(0,246,255,0.05);'>
                <div class='box-stat p-1 mx-1'>
                    <span class='text-success fw-bold' style='font-size:13px;'>" . number_format($stats['verified_pcs']) . "</span>
                    <span class='text-secondary' style='font-size:9px;'>/ " . number_format($stats['total_pcs']) . "</span>
                </div></td>";
        }

        $theadHtml  .= "<th class='text-center pb-2 border-start' style='min-width:140px;color:#000;font-size:11px;'>TOTAL KESELURUHAN</th></tr>";
        $ksoRowHtml .= "<td class='text-center border-start border-info py-2' style='background:rgba(0,246,255,0.05);'>
            <div class='box-stat p-1 mx-1'>
                <span class='text-success fw-bold' style='font-size:13px;'>" . number_format($verifiedKso) . "</span>
                <span class='text-white-50' style='font-size:9px;'>/ " . number_format($totalKso) . "</span>
            </div></td></tr>";
        $pcsRowHtml .= "<td class='text-center border-start border-info py-2' style='background:rgba(0,246,255,0.05);'>
            <div class='box-stat p-1 mx-1'>
                <span class='text-success fw-bold' style='font-size:13px;'>" . number_format($verifiedPcs) . "</span>
                <span class='text-white-50' style='font-size:9px;'>/ " . number_format($totalPcs) . "</span>
            </div></td></tr>";

        $summaryHtml = "<div class='table-responsive rounded'>
            <table class='table table-borderless mb-0' style='color:#fff;'>
                <thead style='border-bottom:1px dashed rgba(0,246,255,0.3);'>{$theadHtml}</thead>
                <tbody>{$ksoRowHtml}{$pcsRowHtml}</tbody>
            </table>
        </div>";

        return response()->json([
            'html'         => $html,
            'summary_html' => $summaryHtml,
            'pic_list'     => array_keys($picStats),
        ]);
    }
}
