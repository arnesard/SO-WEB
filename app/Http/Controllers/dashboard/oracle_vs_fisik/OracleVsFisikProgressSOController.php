<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikProgressSOController extends Controller
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

    public function index(Request $request)
    {
        $selectedGedung = $request->input('gedung');
        $searchAuditor  = $request->input('auditor');

        // 1. Ambil List Gedung
        $gedungs = DB::table('so_all_wh_pic_auditor_db')
            ->select('gedung')
            ->whereNotNull('gedung')
            ->distinct()
            ->pluck('gedung');

        // 2. Siapkan Master Auditor (Pisah Huruf & Angka)
        $auditorsRaw  = DB::table('so_all_wh_pic_auditor_db')->get();
        $auditorRules = [];
        $auditorStats = [];

        $unmappedKey = 'BELUM TER-MAPPING';

        foreach ($auditorsRaw as $aud) {
            $nama = trim($aud->nama);
            $gedung = trim($aud->gedung);

            if (!isset($auditorStats[$nama])) {
                $auditorStats[$nama] = [
                    'auditor'       => $nama,
                    'gedung_list'   => [],
                    'total_data'    => 0,
                    'verified_data' => 0,
                    'total_qty'     => 0,
                    'verified_qty'  => 0
                ];
            }

            if (!empty($gedung) && !in_array($gedung, $auditorStats[$nama]['gedung_list'])) {
                $auditorStats[$nama]['gedung_list'][] = $gedung;
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
                'gedung'    => $gedung,
                'type'      => $type,
                'start_lot' => $startLot,
                'end_lot'   => $endLot,
                'lots'      => $lots ?? [],
            ];
        }

        // Siapkan keranjang siluman
        $auditorStats[$unmappedKey] = [
            'auditor'       => $unmappedKey,
            'gedung_list'   => [],
            'total_data'    => 0,
            'verified_data' => 0,
            'total_qty'     => 0,
            'verified_qty'  => 0,
        ];

        // 3. Tarik Data Grouping APPKSO
        $appksoData = DB::table('so_all_wh_appkso_db')->selectRaw("
            LEFT(nokso, 2) as prefix_gedung,
            SUBSTRING(nokso, 3, 3) as extracted_lot,
            verifikasi_nama,
            COUNT(nokso) as total_data,
            COUNT(CASE WHEN verifikasi_nama IS NOT NULL AND verifikasi_nama != '' THEN nokso END) as verified_data,
            SUM(qty) as total_qty,
            SUM(CASE WHEN verifikasi_nama IS NOT NULL AND verifikasi_nama != '' THEN qty ELSE 0 END) as verified_qty
        ")
            ->groupBy(DB::raw('LEFT(nokso, 2)'), DB::raw('SUBSTRING(nokso, 3, 3)'), 'verifikasi_nama')
            ->get();

        $globalTotal = ['total_data' => 0, 'verified_data' => 0, 'total_qty' => 0, 'verified_qty' => 0];
        $gedungStats = [];

        // 4. DISTRIBUSI OPSI 1: Kepatuhan Area
        foreach ($appksoData as $app) {
            $gedungApp = $this->prefixToGedung[$app->prefix_gedung] ?? 'UNMAPPED';

            $lotApp = strtoupper($app->extracted_lot);
            if (preg_match('/[A-Z]\d{2}/i', $app->extracted_lot, $matches)) {
                $lotApp = strtoupper($matches[0]);
            }

            // Safety check biar nggak error substr
            if (strlen($lotApp) < 3) continue;

            $lotLetter = substr($lotApp, 0, 1);
            $lotNumber = (int) substr($lotApp, 1, 2);

            $verifiedBy = null;
            if (!empty($app->verifikasi_nama)) {
                $verifiedBy = trim($app->verifikasi_nama);
            }

            // Cari pemilik teritori aslinya
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

            if ($selectedGedung && $gedungApp !== $selectedGedung) {
                continue;
            }

            // Hitung Global
            $globalTotal['total_data'] += $app->total_data;
            $globalTotal['verified_data'] += $app->verified_data;
            $globalTotal['total_qty'] += $app->total_qty;
            $globalTotal['verified_qty'] += $app->verified_qty;

            // Hitung Breakdown Gedung
            if (!isset($gedungStats[$gedungApp])) {
                $gedungStats[$gedungApp] = [
                    'lokasi'        => $gedungApp,
                    'total_data'    => 0,
                    'verified_data' => 0,
                    'total_qty'     => 0,
                    'verified_qty'  => 0
                ];
            }
            $gedungStats[$gedungApp]['total_data'] += $app->total_data;
            $gedungStats[$gedungApp]['verified_data'] += $app->verified_data;
            $gedungStats[$gedungApp]['total_qty'] += $app->total_qty;
            $gedungStats[$gedungApp]['verified_qty'] += $app->verified_qty;

            // Masukkan Target (B) ke Pemilik Teritori
            if (isset($auditorStats[$mappedAuditor])) {
                $auditorStats[$mappedAuditor]['total_data'] += $app->total_data;
                $auditorStats[$mappedAuditor]['total_qty'] += $app->total_qty;
            }

            // Masukkan Aktual (A) ke Auditor yang Mengerjakan
            if ($verifiedBy !== null && isset($auditorStats[$verifiedBy])) {
                $auditorStats[$verifiedBy]['verified_data'] += $app->verified_data;
                $auditorStats[$verifiedBy]['verified_qty'] += $app->verified_qty;
            }
        }

        // 5. Finishing Data
        $finalAuditorsData = [];
        foreach ($auditorStats as $key => $stat) {
            // Sembunyikan card siluman jika kosong
            if ($key === $unmappedKey && $stat['total_data'] == 0) {
                continue;
            }

            if (empty($stat['gedung_list'])) {
                $stat['gedung_label'] = '-';
            } else {
                $stat['gedung_label'] = implode(', ', $stat['gedung_list']);
            }

            $finalAuditorsData[] = (object)$stat;
        }

        $auditorsData = collect($finalAuditorsData);

        if ($searchAuditor) {
            $auditorsData = $auditorsData->filter(function ($item) use ($searchAuditor) {
                return stripos($item->auditor, $searchAuditor) !== false;
            });
        }

        // Siluman di paling bawah
        $auditorsData = $auditorsData->sortBy(function ($item) use ($unmappedKey) {
            return $item->auditor === $unmappedKey ? 'ZZZ' : $item->auditor;
        })->values();

        $progressPerGedung = collect($gedungStats)->map(function ($item) {
            $item['progress'] = $item['total_qty'] > 0 ? number_format(($item['verified_qty'] / $item['total_qty']) * 100, 2, '.', '') : 0;
            return (object)$item;
        })->sortBy('lokasi')->values();

        $globalProgress = $globalTotal['total_qty'] > 0 ? number_format(($globalTotal['verified_qty'] / $globalTotal['total_qty']) * 100, 2, '.', '') : 0;

        return view('dashboard.oracle_vs_fisik.oracle_vs_fisik_progress_so', [
            'gedungs'           => $gedungs,
            'selectedGedung'    => $selectedGedung,
            'searchAuditor'     => $searchAuditor,
            'globalSummary'     => (object)$globalTotal,
            'globalProgress'    => $globalProgress,
            'progressPerGedung' => $progressPerGedung,
            'auditorsData'      => $auditorsData
        ]);
    }

    public function getDetail(Request $request)
    {
        $auditorName = $request->input('auditor');
        $selectedGedung = $request->input('gedung');

        $unmappedKey = 'BELUM TER-MAPPING';

        // 1. Tarik Aturan Mapping
        $auditorsRaw  = DB::table('so_all_wh_pic_auditor_db')->get();
        $auditorRules = [];

        foreach ($auditorsRaw as $aud) {
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
                'nama'      => trim($aud->nama),
                'gedung'    => trim($aud->gedung),
                'type'      => $type,
                'start_lot' => $startLot,
                'end_lot'   => $endLot,
                'lots'      => $lots ?? [],
            ];
        }

        // 2. Tarik Data APPKSO
        $appksoData = DB::table('so_all_wh_appkso_db')
            ->select('warehouse', 'nokso', 'oprname', 'verifikasi_nama', 'item', 'deskripsi', 'qty')
            ->get();

        $filteredData = collect();
        $picStats = [];
        $totalKso = $verifiedKso = $totalPcs = $verifiedPcs = 0;

        // 3. Pencocokan Murni & Matrix
        foreach ($appksoData as $app) {
            $noDoc = strtoupper(str_replace(' ', '', trim($app->nokso)));
            if (strlen($noDoc) < 5) continue;

            $prefix    = substr($noDoc, 0, 2);
            $gedungApp = $this->prefixToGedung[$prefix] ?? 'UNMAPPED';
            $lotApp    = substr($noDoc, 2, 3);

            $lotLetter = substr($lotApp, 0, 1);
            $lotNumber = (int) substr($lotApp, 1, 2);

            if ($selectedGedung && $gedungApp !== $selectedGedung) continue;

            $verifiedBy = null;
            if (!empty($app->verifikasi_nama)) {
                $verifiedBy = trim($app->verifikasi_nama);
            }

            // Cari pemilik target
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

            // Tampilkan di Modal HANYA JIKA ini miliknya (Target), ATAU dia yang memverifikasi (Aktual)
            if ($mappedAuditor !== $auditorName && $verifiedBy !== $auditorName) {
                continue;
            }

            $isVerified = ($verifiedBy !== null);
            $picName = !empty($app->oprname) ? ucwords(strtolower(trim($app->oprname))) : 'Unknown PIC';

            if (!isset($picStats[$picName])) {
                $picStats[$picName] = ['total_kso' => 0, 'verified_kso' => 0, 'total_pcs' => 0, 'verified_pcs' => 0];
            }

            if ($mappedAuditor === $auditorName) {
                $picStats[$picName]['total_kso']++;
                $picStats[$picName]['total_pcs'] += $app->qty;
                $totalKso++;
                $totalPcs += $app->qty;
            }

            if ($verifiedBy === $auditorName) {
                $picStats[$picName]['verified_kso']++;
                $picStats[$picName]['verified_pcs'] += $app->qty;
                $verifiedKso++;
                $verifiedPcs += $app->qty;
            }

            $filteredData->push((object)[
                'gedung'    => $gedungApp,
                'nokso'     => $app->nokso,
                'pic_stock' => htmlspecialchars($picName, ENT_QUOTES),
                'auditor'   => $mappedAuditor,
                'item'      => $app->item,
                'deskripsi' => $app->deskripsi,
                'qty'       => $app->qty,
                'status'    => $isVerified ? 'Sudah' : 'Belum'
            ]);
        }

        // 4. Render HTML Body Tabel Utama
        $html = '';
        foreach ($filteredData as $index => $row) {
            $statusBadge = $row->status == 'Sudah'
                ? "<span class='badge bg-success' style='font-size: 9px;'><i data-lucide='check-circle' style='width: 10px; height: 10px;'></i> Sudah Verifikasi</span>"
                : "<span class='badge bg-warning text-dark' style='font-size: 9px;'><i data-lucide='loader-2' style='width: 10px; height: 10px;'></i> Belum Verifikasi</span>";

            $html .= "<tr style='border-bottom: 1px solid rgba(255,255,255,0.1);' class='detail-row' data-status='{$row->status}' data-pic='{$row->pic_stock}'>
                <td class='text-center'>" . ($index + 1) . "</td>
                <td class='text-center text-info fw-bold'>{$row->gedung}</td>
                <td>{$row->nokso}</td>
                <td>{$row->pic_stock}</td>
                <td class='text-warning'>{$row->auditor}</td>
                <td>{$row->item}</td>
                <td><small>{$row->deskripsi}</small></td>
                <td class='text-center text-success fw-bold'>" . number_format($row->qty) . "</td>
                <td class='text-center'>{$statusBadge}</td>
            </tr>";
        }

        if ($filteredData->isEmpty()) {
            $html = "<tr><td colspan='9' class='text-center text-muted py-4'>Tidak ada data scan untuk auditor ini.</td></tr>";
        }

        // 5. Render Matrix Table
        $theadHtml = "<tr><th style='width: 70px; text-align: center; color: #000; font-size: 10px; font-weight: bold; letter-spacing: 1px;'>METRIK</th>";
        $ksoRowHtml = "<tr><td class='text-center border-start border-secondary py-2' style='color: #000; font-weight: bold;'>KSO</td>";
        $pcsRowHtml = "<tr><td class='text-center border-start border-secondary py-2' style='color: #000; font-weight: bold;'>PCS</td>";

        foreach ($picStats as $name => $stats) {
            $theadHtml .= "<th class='text-center pb-2 border-start' style='min-width: 140px; color: #000; font-size: 11px;'>{$name}</th>";

            $ksoRowHtml .= "<td class='text-center border-start border-info py-2' style='background: rgba(0, 246, 255, 0.05);'>
                                <div class='box-stat p-1 mx-1' style='border-color: rgba(0,255,255,0.2); box-shadow: 0 0 5px rgba(0,255,255,0.1);'>
                                    <span class='text-success fw-bold' style='font-size: 13px;'>" . number_format($stats['verified_kso']) . "</span>
                                    <span class='text-secondary' style='font-size: 9px;'>/ " . number_format($stats['total_kso']) . "</span>
                                </div>
                            </td>";

            $pcsRowHtml .= "<td class='text-center border-start border-info py-2' style='background: rgba(0, 246, 255, 0.05);'>
                                <div class='box-stat p-1 mx-1' style='border-color: rgba(0,255,255,0.2); box-shadow: 0 0 5px rgba(0,255,255,0.1);'>
                                    <span class='text-success fw-bold' style='font-size: 13px;'>" . number_format($stats['verified_pcs']) . "</span>
                                    <span class='text-secondary' style='font-size: 9px;'>/ " . number_format($stats['total_pcs']) . "</span>
                                </div>
                            </td>";
        }

        $theadHtml .= "<th class='text-center pb-2 border-start' style='min-width: 140px; color: #000; font-size: 11px;'>TOTAL KESELURUHAN</th></tr>";

        $ksoRowHtml .= "<td class='text-center border-start border-info py-2' style='background: rgba(0, 246, 255, 0.05);'>
                            <div class='box-stat p-1 mx-1' style='border-color: rgba(0,255,255,0.2); box-shadow: 0 0 5px rgba(0,255,255,0.1);'>
                                <span class='text-success fw-bold' style='font-size: 13px;'>" . number_format($verifiedKso) . "</span>
                                <span class='text-white-50' style='font-size: 9px;'>/ " . number_format($totalKso) . "</span>
                            </div>
                        </td></tr>";

        $pcsRowHtml .= "<td class='text-center border-start border-info py-2' style='background: rgba(0, 246, 255, 0.05);'>
                            <div class='box-stat p-1 mx-1' style='border-color: rgba(0,255,255,0.2); box-shadow: 0 0 5px rgba(0,255,255,0.1);'>
                                <span class='text-success fw-bold' style='font-size: 13px;'>" . number_format($verifiedPcs) . "</span>
                                <span class='text-white-50' style='font-size: 9px;'>/ " . number_format($totalPcs) . "</span>
                            </div>
                        </td></tr>";

        $summaryHtml = "<div class='table-responsive rounded'>
            <table class='table table-borderless mb-0' style='color: #fff;'>
                <thead style='border-bottom: 1px dashed rgba(0, 246, 255, 0.3);'>{$theadHtml}</thead>
                <tbody>{$ksoRowHtml}{$pcsRowHtml}</tbody>
            </table>
        </div>";

        return response()->json([
            'html' => $html,
            'summary_html' => $summaryHtml,
            'pic_list' => array_keys($picStats)
        ]);
    }
}
