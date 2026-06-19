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
                        <span
                            style="font-size: 0.80rem; font-weight: 700; color: #00f6ff; letter-spacing: 1px;">Logistic
                            Dept.</span>
                    </div>
                </div>

                <h5 class="title-glow mb-0 text-center flex-grow-1">
                    LIVE PROGRESS STOCK OPNAME
                    @if ($selectedGedung)
                        <span class="text-warning"> - {{ $selectedGedung }}</span>
                    @endif
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
                                    // LOGIKA STATUS CARD (Opsi 1)
                                    $bgClass = '';
                                    $statusText = 'OPEN';

                                    if ($row->total_data > 0) {
                                        if ($row->verified_data == 0) {
                                            $statusText = 'OPEN';
                                            $bgClass = '';
                                        } elseif ($row->verified_data > 0 && $row->verified_data < $row->total_data) {
                                            $statusText = 'PROSES';
                                            $bgClass = 'card-process';
                                        } elseif ($row->verified_data == $row->total_data) {
                                            $statusText = 'SELESAI';
                                            $bgClass = 'card-completed';
                                        }
                                    }

                                    $isSiluman = $row->auditor == 'BELUM TER-MAPPING';
                                @endphp

                                <div>
                                    <div class="glass card-auditor {{ $bgClass }} card-tv d-flex flex-column justify-content-between h-100"
                                        onclick="openDetailModal('{{ $row->auditor }}', '{{ $selectedGedung }}')"
                                        style="cursor: pointer;">

                                        <div
                                            class="d-flex justify-content-center mb-1 pb-1 border-bottom border-secondary">
                                            @if ($statusText == 'SELESAI')
                                                <span
                                                    class="badge bg-success d-flex align-items-center gap-1 shadow-sm">
                                                    <i data-lucide="check-circle" style="width: 18px; height: 8px;"></i>
                                                    Selesai
                                                </span>
                                            @elseif ($statusText == 'PROSES')
                                                <span
                                                    class="badge bg-light text-dark d-flex align-items-center gap-1 shadow-sm fw-bold">
                                                    <i data-lucide="loader-2" class="lucide-spin"
                                                        style="width: 18px; height: 8px;"></i> Proses
                                                </span>
                                            @else
                                                <span
                                                    class="badge bg-warning text-dark d-flex align-items-center gap-1 shadow-sm">
                                                    <i data-lucide="circle-dashed"
                                                        style="width: 18px; height: 8px;"></i> Open
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex justify-content-center align-items-center mt-1 mb-1">
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

                                        <div class="text-center mt-1 mb-2">
                                            <span class="info-label text-white d-block mb-1">AUDITOR</span>
                                            <span
                                                class="info-value {{ $isSiluman ? 'text-danger' : 'text-warning' }} d-flex justify-content-center align-items-center gap-1"
                                                style="{{ $isSiluman ? 'font-size:9px;' : '' }}">
                                                <i data-lucide="{{ $isSiluman ? 'alert-triangle' : 'user' }}"
                                                    style="width: 10px; height: 10px;"></i>
                                                {{ $row->auditor }}
                                            </span>
                                        </div>

                                        <div class="d-flex flex-column gap-1 mt-auto">
                                            <div
                                                class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
                                                <span class="info-label mb-0 text-start text-white">KSO :</span>
                                                <div class="text-end">
                                                    <span
                                                        class="text-success info-value">{{ number_format($row->verified_data) }}</span>
                                                    <span class="text-secondary text-white" style="font-size: 8px;">/
                                                        {{ number_format($row->total_data) }}</span>
                                                </div>
                                            </div>
                                            <div
                                                class="box-stat p-1 d-flex justify-content-between align-items-center px-2">
                                                <span class="info-label mb-0 text-start text-white">PCS :</span>
                                                <div class="text-end">
                                                    <span
                                                        class="text-success info-value">{{ number_format($row->verified_qty) }}</span>
                                                    <span class="text-secondary text-white" style="font-size: 8px;">/
                                                        {{ number_format($row->total_qty) }}</span>
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
                            <form method="GET" action="{{ route('progress.index') }}"
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

                                <a href="{{ route('dashboard.index') }}"
                                    class="btn btn-sm btn-outline-danger w-100 fw-bold mt-1"
                                    style="font-size: 9px; letter-spacing: 1px;">
                                    <i data-lucide="log-out" style="width: 10px; height: 10px;" class="me-1"></i>
                                    KEMBALI
                                </a>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="glass p-3 text-center">
                    <h6 class="text-white-50 mb-0" style="font-size: 10px; letter-spacing: 1px;">TOTAL PROGRESS</h6>
                    <h1 class="title-glow display-5 mb-2">{{ $globalProgress }}%</h1>

                    <div class="progress-bg w-100 mx-auto mt-0 mb-0" style="max-width: 85%; height: 8px;">
                        <div class="progress-bar-glow" style="width: {{ min(100, $globalProgress) }}%;"></div>
                    </div>

                    <div class="d-flex flex-column gap-0 mt-2 mb-0">
                        <div class="box-stat p-0 d-flex justify-content-between align-items-center px-1">
                            <span class="text-white-50 fw-bold mb-0"
                                style="font-size: 9px; letter-spacing: 0.5px;">KSO :</span>
                            <div>
                                <span class="title-glow text-success fw-bold"
                                    style="font-size: 13px;">{{ number_format($globalSummary->verified_data ?? 0) }}</span>
                                <span class="text-white-50" style="font-size: 10px;">/
                                    {{ number_format($globalSummary->total_data ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="box-stat p-0 d-flex justify-content-between align-items-center px-1">
                            <span class="text-white-50 fw-bold mb-0"
                                style="font-size: 9px; letter-spacing: 0.5px;">PCS :</span>
                            <div>
                                <span class="title-glow text-success fw-bold"
                                    style="font-size: 13px;">{{ number_format($globalSummary->verified_qty ?? 0) }}</span>
                                <span class="text-white-50" style="font-size: 10px;">/
                                    {{ number_format($globalSummary->total_qty ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass p-3 flex-grow-1">
                    <h6 class="text-info border-bottom border-secondary pb-2 mb-3" style="font-size: 11px;">Breakdown
                        Per Gedung</h6>
                    @forelse($progressPerGedung as $gedung)
                        <div class="glass p-2 mb-2"
                            style="background: rgba(0,0,0,0.2); border-color: rgba(255,255,255,0.03);">
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <span class="fw-bold text-light"
                                    style="font-size: 11px;">{{ $gedung->lokasi }}</span>
                                <small class="text-info fw-bold"
                                    style="font-size: 10px;">{{ $gedung->progress }}%</small>
                            </div>
                            <div class="progress-bg w-100 mb-2"
                                style="height: 5px; background: rgba(255,255,255,0.05);">
                                <div class="progress-bar-glow" style="width: {{ min(100, $gedung->progress) }}%;">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-1" style="font-size: 8px;">
                                <span class="text-white-50">KSO :</span>
                                <span>
                                    <span
                                        class="title-glow text-success fw-bold">{{ number_format($gedung->verified_data) }}</span>
                                    /
                                    <span class="text-white-50">{{ number_format($gedung->total_data) }}</span>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between" style="font-size: 8px;">
                                <span class="text-white-50">PCS :</span>
                                <span>
                                    <span
                                        class="title-glow text-success fw-bold">{{ number_format($gedung->verified_qty) }}</span>
                                    /
                                    <span class="text-white-50">{{ number_format($gedung->total_qty) }}</span>
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-white-50 mt-4" style="font-size: 10px;">Belum ada data lokasi.
                        </div>
                    @endforelse
                </div>

            </div>

        </div>
    </div>

    {{-- MODAL --}}
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
    <script src="{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_progress_so.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>

</html>
