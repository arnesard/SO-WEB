<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Stock Opname - Fullscreen Dashboard</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        /* ===============================
           TV MONITORING 55 INCH STYLE
        =============================== */
        .tv-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            grid-auto-rows: 1fr;
            height: 100%;
            gap: 0.4rem;
            align-items: stretch;
        }

        .card-tv {
            padding: 8px !important;
            height: 100%;
            cursor: pointer;
        }

        .card-tv .info-label {
            font-size: 8px;
            color: #8c98a4;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .card-tv .info-value {
            font-size: 11px;
            font-weight: 700;
        }

        .card-tv .badge {
            font-size: 8px !important;
            padding: 3px 6px !important;
        }

        /* ===============================
           GLOBAL FUTURISTIC STYLE
        =============================== */
        body {
            background-color: #0b0f19;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .glass {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        .title-glow {
            text-shadow: 0 0 15px rgba(0, 255, 255, 0.6);
            letter-spacing: 1px;
            color: #00f6ff;
            font-weight: bold;
        }

        .row-header {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        /* Base Card Styling (Untuk status OPEN) */
        .card-auditor {
            transition: all 0.2s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.03) 0%, rgba(0, 0, 0, 0.2) 100%);
        }

        .card-auditor:hover {
            border-color: rgba(0, 246, 255, 0.4);
            background: rgba(0, 246, 255, 0.05);
            transform: translateY(-3px);
        }

        /* Card: PROSES (Warna Putih/Glow Putih) */
        .card-process {
            border-color: rgba(255, 255, 255, 0.5) !important;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.15) 0%, rgba(0, 0, 0, 0.3) 100%) !important;
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.2);
        }

        /* Card: SELESAI (Hijau) */
        .card-completed {
            border-color: rgba(0, 255, 153, 0.5) !important;
            background: linear-gradient(145deg, rgba(0, 255, 153, 0.08) 0%, rgba(0, 0, 0, 0.3) 100%) !important;
            box-shadow: 0 0 10px rgba(0, 255, 153, 0.15);
        }

        .box-stat {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        .progress-bg {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar-glow {
            height: 100%;
            background: linear-gradient(90deg, #00f6ff, #00ff99);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.6);
            transition: width 1s ease-in-out;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0, 255, 255, 0.3);
            border-radius: 10px;
        }

        .lucide-spin {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        /* Form Filter */
        .form-select-tv {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 255, 255, 0.2);
            color: #00f6ff;
            font-size: 11px;
            font-weight: bold;
        }

        .form-select-tv:focus {
            background-color: rgba(0, 0, 0, 0.5);
            color: #00f6ff;
            box-shadow: 0 0 5px rgba(0, 255, 255, 0.5);
            border-color: #00f6ff;
        }

        [data-bs-toggle="collapse"][aria-expanded="true"] #searchChevron {
            transform: rotate(180deg);
        }

        /* Efek Pulse untuk Judul */
        .title-glow {
            animation: pulsate 2s ease-in-out infinite;
        }

        @keyframes pulsate {
            0% {
                opacity: 0.6;
                text-shadow: 0 0 10px rgba(0, 255, 255, 0.4);
            }

            50% {
                opacity: 1;
                text-shadow: 0 0 25px rgba(0, 255, 255, 0.8), 0 0 5px rgba(0, 255, 255, 0.6);
            }

            100% {
                opacity: 0.6;
                text-shadow: 0 0 10px rgba(0, 255, 255, 0.4);
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid p-3 vh-100 d-flex flex-column">

        <div class="row-header">
            <div class="glass px-4 py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/logo-gt.png') }}" alt="Logo"
                        style="height: 38px; filter: brightness(0) invert(1);">
                    <div class="d-flex flex-column lh-1">
                        <span class="text-white"
                            style="font-size: 1rem; font-weight: 800; letter-spacing: 0.5px; text-shadow: 0 0 10px rgba(255,255,255,0.3);">PT.
                            Gajah Tunggal Tbk.,</span>
                        <span style="font-size: 0.80rem; font-weight: 700; color: #00f6ff; letter-spacing: 1px;">Gudang
                            Ban B Dept.</span>
                    </div>
                </div>

                <h5 class="title-glow mb-0 text-center flex-grow-1" style="font-size: 2rem;">
                    LIVE PROGRESS STOCK OPNAME
                </h5>

                <div class="text-end" style="min-width: 10px;">
                    <span id="liveClock" class="title-glow fs-5" style="font-family: monospace;">00:00:00</span><br>
                    <small class="text-white-5" id="liveDate"
                        style="font-size: 1rem; font-weight: 800; letter-spacing: 0.5px; text-shadow: 0 0 10px rgba(255,255,255,0.3);"></small>
                </div>
            </div>
        </div>

        <div class="row g-3 flex-grow-1 overflow-hidden">
            <div class="col-lg-10 h-100">
                <div class="glass p-3 h-100 d-flex flex-column">
                    <div class="flex-grow-1 pe-1" id="auditor-cards-container">
                        <div class="tv-grid">

                            @forelse($auditorsData as $row)
                                @php
                                    // LOGIKA STATUS CARD OPSI 1
                                    $bgClass = '';
                                    $statusText = 'OPEN';

                                    // Kalau total data 0, biarin aja statusnya OPEN
                                    if ($row->total_data > 0) {
                                        if ($row->verified_data == 0) {
                                            $statusText = 'OPEN';
                                        } elseif ($row->verified_data > 0 && $row->verified_data < $row->total_data) {
                                            $statusText = 'PROSES';
                                            $bgClass = 'card-process'; // Warna Putih
                                        } elseif ($row->verified_data == $row->total_data) {
                                            $statusText = 'SELESAI';
                                            $bgClass = 'card-completed'; // Warna Hijau
                                        }
                                    }

                                    $isSiluman = $row->auditor == 'BELUM TER-MAPPING';
                                @endphp

                                <div>
                                    <div class="glass card-auditor {{ $bgClass }} card-tv d-flex flex-column justify-content-between h-100"
                                        onclick="openDetailModal('{{ $row->auditor }}', '{{ $selectedGedung }}')"
                                        style="cursor: pointer;">

                                        <div
                                            class="d-flex justify-content-center mb-auto pb-1 border-bottom border-secondary">
                                            @if ($statusText == 'SELESAI')
                                                <span
                                                    class="badge bg-success d-flex align-items-center gap-1 shadow-sm">
                                                    <i data-lucide="check-circle"
                                                        style="width: 20px; height: 12px;"></i>
                                                    Selesai
                                                </span>
                                            @elseif ($statusText == 'PROSES')
                                                <span
                                                    class="badge bg-light text-dark d-flex align-items-center gap-1 shadow-sm fw-bold">
                                                    <i data-lucide="loader-2" class="lucide-spin"
                                                        style="width: 20px; height: 12px;"></i> Proses
                                                </span>
                                            @else
                                                <span
                                                    class="badge bg-warning text-dark d-flex align-items-center gap-1 shadow-sm">
                                                    <i data-lucide="circle-dashed"
                                                        style="width: 20px; height: 12px;"></i> Open
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-center align-items-center mt-auto mb-auto">
                                            <div class="d-flex align-items-center gap-1">
                                                <i data-lucide="factory"
                                                    style="width: 12px; height: 12px; color: #8c98a4;"></i>
                                                <span class="fw-bold text-info text-truncate"
                                                    style="font-size: 11px; max-width: 100px;"
                                                    title="{{ $row->gedung_label }}">
                                                    {{ $row->gedung_label }}
                                                </span>
                                            </div>
                                        </div>

                                       {{-- NAMA AUDITOR (tanpa icon & label "AUDITOR") --}}
<div class="text-center mt-0 mb-0">
    @php
        $namaParts = explode(' (', $row->auditor);
        $namaUtama = $nameParts[0] ?? $row->auditor;
        $namaKode  = isset($namaParts[1]) ? '(' . $namaParts[1] : '';
        $progress  = $row->total_data > 0
            ? round(($row->verified_data / $row->total_data) * 100)
            : 0;
    @endphp

    <span class="{{ $isSiluman ? 'text-danger' : 'text-warning' }} fw-bold d-block text-center"
    style="font-size: 13px; line-height: 1.2;">
    {{ $row->auditor }}
</span>
</div>

                                        <div class="d-flex flex-column gap-1 mt-auto">
    {{-- DURASI + % --}}
    @php
        $progress = $row->total_data > 0
            ? round(($row->verified_data / $row->total_data) * 100)
            : 0;
    @endphp
    <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
      @php
           $durColor = '#8c98a4'; // default abu (belum scan)
if (!is_null($row->scan_duration)) {
    if ($row->scan_duration < 30) $durColor = '#00f6ff';      // biru
    elseif ($row->scan_duration < 60) $durColor = '#00ff99';  // hijau
    elseif ($row->scan_duration < 90) $durColor = '#ffc107';  // kuning
    else $durColor = '#ff4d4d';                                // merah
}
        @endphp
        <span style="font-size:9px; color: {{ $durColor }};">
            <i data-lucide="clock" style="width:9px;height:9px;"></i>
            @if(!is_null($row->scan_duration))
                {{ $row->scan_duration >= 60
                    ? floor($row->scan_duration/60).'j '.($row->scan_duration%60).'m'
                    : $row->scan_duration.' Menit' }}
            @else
                -
            @endif
        </span>
        <span class="fw-bold" style="font-size:11px; color: {{ $progress >= 100 ? '#00ff99' : ($progress > 0 ? '#00f6ff' : '#8c98a4') }};">
            {{ $progress }}%
        </span>
    </div>

    {{-- KSO --}}
    <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
        <span class="info-label mb-0 text-start text-white">KSO :</span>
        <div class="text-end">
            <span class="text-success info-value">{{ number_format($row->verified_data) }}</span>
            <span class="text-secondary text-white" style="font-size: 11px;">/ {{ number_format($row->total_data) }}</span>
        </div>
    </div>

    {{-- PCS --}}
    <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
        <span class="info-label mb-0 text-start text-white">PCS :</span>
        <div class="text-end">
            <span class="text-success info-value">{{ number_format($row->verified_qty) }}</span>
            <span class="text-secondary text-white" style="font-size: 11px;">/ {{ number_format($row->total_qty) }}</span>
        </div>
    </div>
</div>

                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-white-50 py-5 w-100">
                                    <div class="d-flex justify-content-center mb-2">
                                        <i data-lucide="folder-open"
                                            style="width: 40px; height: 40px; opacity: 0.5;"></i>
                                    </div>
                                    <small>Data belum tersedia untuk filter ini.</small>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-2 h-100 d-flex flex-column gap-3" id="right-panel-container">
                <div class="glass p-3">
                    <div class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse"
                        data-bs-target="#collapseSearch"
                        aria-expanded="{{ request('auditor') || request('gedung') ? 'true' : 'false' }}"
                        style="cursor: pointer;">
                        <h6 class="text-info mb-0" style="font-size: 11px; letter-spacing: 1px;">
                            <i data-lucide="search" style="width: 10px; height: 10px;" class="me-1"></i> PENCARIAN
                        </h6>
                        <i data-lucide="chevron-down" id="searchChevron"
                            style="width: 14px; height: 14px; color: #00f6ff; transition: transform 0.3s;"></i>
                    </div>

                    <div class="collapse {{ request('auditor') || request('gedung') ? 'show' : '' }} mt-2"
                        id="collapseSearch">
                        <div class="border-top border-secondary pt-2">
                            <form method="GET" action="{{ route('auto_progress.index') }}"
                                class="d-flex flex-column gap-2">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="auditor" class="form-control form-select-tv"
                                        placeholder="Cari Auditor..." value="{{ request('auditor') }}"
                                        style="border-right: none;">
                                    <button type="submit" class="btn btn-outline-info d-flex align-items-center"
                                        style="background: rgba(0,0,0,0.3); border-color: rgba(0, 255, 255, 0.2);">
                                        <i data-lucide="search" style="width: 12px; height: 12px;"></i>
                                    </button>
                                </div>
                                <select name="gedung" class="form-select form-select-sm form-select-tv"
                                    onchange="this.form.submit()">
                                    <option value="">-- SEMUA GEDUNG --</option>
                                    @foreach ($gedungs as $g)
                                        <option value="{{ $g }}"
                                            {{ request('gedung') == $g ? 'selected' : '' }}>{{ $g }}
                                        </option>
                                    @endforeach
                                </select>
                                {{-- <a href="{{ route('dashboard.index') }}"
                                    class="btn btn-sm btn-outline-danger w-100 fw-bold mt-1"
                                    style="font-size: 9px; letter-spacing: 1px;">
                                    <i data-lucide="log-out" style="width: 10px; height: 10px;" class="me-1"></i>
                                    KEMBALI
                                </a> --}}
                            </form>
                        </div>
                    </div>
                </div>

             <div class="glass p-3">
   <h6 class="text-info text-center border-bottom border-secondary pb-2 mb-2"
    style="font-size: 18px;">TOTAL PROGRESS</h6>

<h1 class="title-glow display-5 text-center mb-0">{{ $globalProgress }}%</h1>

{{-- Durasi tengah warna cyan --}}
<div class="text-center mb-1">
    <span class="title-glow fw-bold" style="font-size:12px;">
        <i data-lucide="clock" style="width:10px;height:10px;"></i>
        @if(!is_null($globalDuration))
            {{ $globalDuration >= 60 ? floor($globalDuration/60).'j '.($globalDuration%60).'m' : $globalDuration.' Menit' }}
        @else - @endif
    </span>
</div>

<div class="progress-bg w-100 mx-auto mb-2" style="max-width: 85%; height: 8px;">
    <div class="progress-bar-glow" style="width: {{ min(100, $globalProgress) }}%;"></div>
</div>

<div class="d-flex flex-column gap-1">
    {{-- KSO --}}
    <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
        <span class="text-white-50 fw-bold" style="font-size:11px;">KSO :</span>
        <div>
            <span class="text-success fw-bold" style="font-size:13px;">{{ number_format($globalSummary->verified_data ?? 0) }}</span>
            <span class="text-white-50" style="font-size:10px;">/ {{ number_format($globalSummary->total_data ?? 0) }}</span>
        </div>
    </div>
    {{-- PCS --}}
    <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
        <span class="text-white-50 fw-bold" style="font-size:11px;">PCS :</span>
        <div>
            <span class="text-success fw-bold" style="font-size:13px;">{{ number_format($globalSummary->verified_qty ?? 0) }}</span>
            <span class="text-white-50" style="font-size:10px;">/ {{ number_format($globalSummary->total_qty ?? 0) }}</span>
        </div>
    </div>
</div>
</div>

<div class="glass p-3 flex-grow-1" style="overflow-y: auto;">
    <h6 class="text-info text-center border-bottom border-secondary pb-2 mb-1"
        style="font-size: 18px;">Breakdown Per Gedung</h6>

    @forelse($progressPerGedung as $gedung)
        @php $dur = $durasiPerGedung[$gedung->lokasi] ?? null; @endphp
        <div class="glass p-2 mb-1" style="background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.03);">

            {{-- Baris 1: BPW01 (durasi) di kiri, % di kanan --}}
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold text-light" style="font-size:13px;">
                    {{ $gedung->lokasi }}
                    <span class="title-glow" style="font-size:10px; font-weight:normal;">
                        <i data-lucide="clock" style="width:9px;height:9px;"></i>
                        @if(!is_null($dur))
                            {{ $dur >= 60 ? floor($dur/60).'j '.($dur%60).'m' : $dur.' Menit' }}
                        @else - @endif
                    </span>
                </span>
                <span class="fw-bold" style="font-size:11px; color:#00f6ff;">{{ $gedung->progress }}%</span>
            </div>

            <div class="progress-bg w-100 mb-1" style="height: 5px; background: rgba(255,255,255,0.05);">
                <div class="progress-bar-glow" style="width: {{ min(100, $gedung->progress) }}%;"></div>
            </div>

            {{-- KSO --}}
            <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2 mb-1">
                <span class="text-white-50" style="font-size:10px;">KSO :</span>
                <div>
                    <span class="text-success fw-bold" style="font-size:11px;">{{ number_format($gedung->verified_data) }}</span>
                    <span class="text-white-50" style="font-size:10px;">/ {{ number_format($gedung->total_data) }}</span>
                </div>
            </div>

            {{-- PCS --}}
            <div class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
                <span class="text-white-50" style="font-size:10px;">PCS :</span>
                <div>
                    <span class="text-success fw-bold" style="font-size:11px;">{{ number_format($gedung->verified_qty) }}</span>
                    <span class="text-white-50" style="font-size:10px;">/ {{ number_format($gedung->total_qty) }}</span>
                </div>
            </div>

        </div>
    @empty
        <div class="text-center text-white-50 mt-4" style="font-size: 10px;">Belum ada data lokasi.</div>
    @endforelse
</div>

    {{-- MODAL DETAIL --}}
    <div class="modal fade" id="modalDetailAuditor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg"
                style="background-color: #0b0f19; border: 1px solid rgba(0, 255, 255, 0.3) !important;">
                <div class="modal-header border-secondary" style="background: rgba(255, 255, 255, 0.05);">
                    <h5 class="modal-title title-glow" style="font-size: 15px;">
                        <i data-lucide="file-text" class="me-2" style="width: 18px; height: 18px;"></i>
                        DETAIL DATA: <span id="modalAuditorName" class="text-warning"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="glass m-3 p-3"
                        style="border-color: rgba(0, 246, 255, 0.4); box-shadow: 0 0 15px rgba(0, 246, 255, 0.1);">
                        <div
                            class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                            <h6 class="title-glow mb-0 d-flex align-items-center gap-2"
                                style="font-size: 13px; letter-spacing: 1px;">
                                <i data-lucide="users" style="width: 16px; height: 16px;"></i> MATRIX PIC STOCK
                            </h6>
                            <div class="d-flex gap-2">
                                <select id="modalFilterPic" class="form-select form-select-sm form-select-tv"
                                    style="width: 190px;" onchange="filterModalTable()">
                                    <option value="All">-- SEMUA PIC STOCK --</option>
                                </select>
                                <select id="modalFilterStatus" class="form-select form-select-sm form-select-tv"
                                    style="width: 180px;" onchange="filterModalTable()">
                                    <option value="All">-- SEMUA STATUS --</option>
                                    <option value="Sudah">SUDAH VERIFIKASI</option>
                                    <option value="Belum">BELUM VERIFIKASI</option>
                                </select>
                            </div>
                        </div>

                        <div id="modalSummaryContainer">
                            <div class="text-center py-3">
                                <i data-lucide="loader-2" class="lucide-spin text-info"
                                    style="width: 20px; height: 20px;"></i>
                                <span class="text-white-50 ms-2" style="font-size: 11px;">Membangun matrix
                                    data...</span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 65vh;">
                        <table class="table table-dark table-hover mb-0"
                            style="font-size: 11px; --bs-table-bg: transparent; white-space: nowrap;">
                            <thead
                                style="position: sticky; top: 0; z-index: 10; background-color: #1a202c; border-bottom: 2px solid #00f6ff;">
                                <tr style="color: #00f6ff;">
                                    <th class="text-center" style="width: 5%;">NO</th>
                                    <th class="text-center" style="width: 8%;">GEDUNG</th>
                                    <th style="width: 12%;">NOKSO</th>
                                    <th style="width: 15%;">NAMA PIC STOCK</th>
                                    <th style="width: 15%;">NAMA AUDITOR</th>
                                    <th style="width: 12%;">ITEM</th>
                                    <th style="width: 15%;">DESKRIPSI</th>
                                    <th class="text-center" style="width: 8%;">QTY</th>
                                    <th class="text-center" style="width: 10%;">KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody id="detailAuditorTbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/appkso/auto_progress_so.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>

</html>
