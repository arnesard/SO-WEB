@extends('layouts.app')

@section('content')
    <style>
        body,
        html {
            overflow: hidden;
            background-color: #f8f9fa;
        }

        .fixed-wrapper {
            display: flex;
            flex-direction: column;
            height: calc(110vh - 70px);
            padding-bottom: 20px;
        }

        /* Styling Tabel Paket Mahal */
        #mainTable thead th {
            background-color: #132541 !important;
            color: white !important;
            letter-spacing: 0.5px;
            font-size: 10px;
            border: none;
            padding: 10px 5px;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #fe6807;
            border-radius: 10px;
        }

        #tableBody tr:hover {
            background-color: #fff4e6 !important;
            transition: 0.2s;
        }

        .footer-stat-box {
            background: linear-gradient(45deg, #1e293b, #334155);
            color: white;
            padding: 8px;
            border-radius: 8px;
        }

        .bg-orange {
            background-color: #fe6807 !important;
        }

        .text-orange {
            color: #fe6807 !important;
        }

        .border-orange {
            border-color: #fe6807 !important;
        }

        /* ... existing styles ... */
        .bg-orange {
            background-color: #fe6807 !important;
        }

        .text-orange {
            color: #fe6807 !important;
        }
    </style>

    <div class="container-fluid fixed-wrapper">
        {{-- Container Utama Modul APPKSO --}}
        <div id="appkso-section" class="content-section active" style="height: calc(100vh - 110px); min-height: 500px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 1px solid #fe6807 !important; border-radius: 12px; overflow: hidden;">

                {{-- 1. Header Card Utama (Format Sama dengan Report Daily) --}}
                {{-- 1. Header Card Utama --}}
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #fe6807; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0; overflow: hidden;">

                    {{-- Title: Dibuat flex-shrink-0 supaya tulisannya nggak kegencet --}}
                    <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center flex-shrink-0 me-3">
                        <i data-lucide="clipboard-check" class="me-2" style="width: 16px; height: 16px;"></i>
                        APPKSO (Aplikasi Stock Opname EDP)
                    </h5>

                    {{-- Wrapper Navbar: Pakai flex-grow-1 supaya memakan sisa layar dan mepet kanan otomatis --}}
                    <div class="d-flex justify-content-end align-items-center flex-grow-1 ms-auto"
                        style="overflow-x: auto; scrollbar-width: none;">

                        {{-- Navbar Navigasi Modul --}}
                        @include('appkso.navbar')

                    </div>
                </div>

                {{-- 2. Body Utama (Menggunakan h-100 dan overflow hidden) --}}
                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden;">
                    <div class="row g-3 h-100">

                        {{-- KOLOM KIRI (Control Panel & Resume) --}}
                        <div class="col-md-4 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm h-100 d-flex flex-column"
                                style="border-radius: 10px; overflow: hidden;">
                                <div class="card-header bg-light py-2 text-center" style="flex-shrink: 0;">
                                    <h6 class="mb-0 small fw-bold text-uppercase" style="font-size: 11px; color: #fe6807;">
                                        Project Control Panel</h6>
                                </div>

                                {{-- A. Pilih Project --}}
                                <div class="p-2" style="flex-shrink: 0;">
                                    <div class="card shadow-sm border-0 mb-1"
                                        style="background-color: rgba(254, 104, 7, 0.05); border: 1px solid rgba(254, 104, 7, 0.2) !important; border-radius: 10px;">
                                        <div class="card-body p-2">
                                            <form action="{{ route('appkso.index') }}" method="GET" id="filterForm">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <label class="small fw-bold text-muted text-uppercase"
                                                        style="font-size: 9px;">SO Active</label>
                                                    <span class="badge bg-orange text-white border-0 py-0 px-2"
                                                        style="font-size: 9px;">
                                                        Counter:
                                                        {{ $list_kso->where('so_name', $selected_so)->first()->def_counter ?? '-' }}
                                                    </span>
                                                </div>
                                                <select id="so_select" name="so_name"
                                                    class="form-select form-select-sm border-orange shadow-sm fw-bold"
                                                    style="font-size: 11px;"
                                                    onchange="document.getElementById('filterForm').submit()">
                                                    @foreach ($list_kso as $kso)
                                                        <option value="{{ $kso->so_name }}"
                                                            data-counter="{{ $kso->def_counter }}"
                                                            {{ $selected_so == $kso->so_name ? 'selected' : '' }}>
                                                            {{ $kso->so_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- B. Stat Box (3 Kolom) --}}
                                <div class="px-2 pb-2" style="flex-shrink: 0;">
                                    <div class="row g-1 text-center">
                                        <div class="col-4">
                                            <div class="footer-stat-box h-100 d-flex flex-column justify-content-center">
                                                <small class="fw-bold opacity-75 d-block" style="font-size: 8px;">TOTAL
                                                    DOC</small>
                                                <span class="fw-black"
                                                    style="font-size: 12px;">{{ number_format(count($activities)) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div
                                                class="bg-white border-bottom border-orange border-3 shadow-sm h-100 d-flex flex-column justify-content-center rounded-3 p-1">
                                                <small class="fw-bold text-muted d-block" style="font-size: 8px;">TOTAL
                                                    QTY</small>
                                                <span class="fw-black text-dark"
                                                    style="font-size: 12px;">{{ number_format($summary['total_pcs']) }}</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div
                                                class="bg-white border-bottom border-dark border-3 shadow-sm h-100 d-flex flex-column justify-content-center rounded-3 p-1">
                                                <small class="fw-bold text-muted d-block" style="font-size: 8px;">TOTAL
                                                    SKU</small>
                                                <span class="fw-black text-orange"
                                                    style="font-size: 12px;">{{ number_format($summary['unique_items']) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- C. Resume Tabel (OE & OK) --}}
                                <div class="px-2 pb-2 overflow-auto custom-scroll" style="height: 35vh; flex-shrink: 0;">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="bg-dark text-white text-center py-1 fw-bold rounded-top"
                                                style="font-size: 10px;">GRADE OE</div>
                                            <table class="table table-sm table-bordered mb-0"
                                                style="font-size: 9px; border-color: #eee;">
                                                <tbody class="bg-white">
                                                    @foreach ($resume_oe as $idx => $roe)
                                                        <tr>
                                                            <td class="text-center text-muted" width="20">
                                                                {{ $idx + 1 }}</td>
                                                            <td class="fw-bold text-truncate" style="max-width: 65px;">
                                                                {{ $roe['pattern'] }}</td>
                                                            <td class="text-end fw-bold text-orange">
                                                                {{ number_format($roe['total_qty']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-orange text-white text-center py-1 fw-bold rounded-top"
                                                style="font-size: 10px;">GRADE OK</div>
                                            <table class="table table-sm table-bordered mb-0"
                                                style="font-size: 9px; border-color: #eee;">
                                                <tbody class="bg-white">
                                                    @foreach ($resume_ok as $idx => $rok)
                                                        <tr>
                                                            <td class="text-center text-muted" width="20">
                                                                {{ $idx + 1 }}</td>
                                                            <td class="fw-bold text-truncate" style="max-width: 65px;">
                                                                {{ $rok['pattern'] }}</td>
                                                            <td class="text-end fw-bold text-dark">
                                                                {{ number_format($rok['total_qty']) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- D. BPW Slot (Sisa Tinggi) --}}
                                <div class="flex-grow-1 p-2 bg-light border-top mt-auto custom-scroll"
                                    style="overflow-y: auto;">
                                    <div class="d-flex flex-column gap-2">
                                        @foreach ($resume_gedung as $gedung => $stats)
                                            <div
                                                class="p-2 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                                                <span class="small fw-bold text-muted">{{ $gedung }}</span>
                                                <div class="d-flex gap-1">
                                                    <span class="badge bg-dark d-flex align-items-center"
                                                        title="Total Dokumen (KSO)">
                                                        KSO: {{ number_format($stats['kso']) }}
                                                    </span>
                                                    <span class="badge bg-orange d-flex align-items-center"
                                                        title="Total Quantity (PCS)">
                                                        PCS: {{ number_format($stats['pcs']) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN (Tabel Resume PIC & Detail Transaction) --}}
                        <div class="col-md-8 d-flex flex-column h-100 gap-2" style="min-height: 0;">

                            {{-- 1. CARD RESUME PIC STOCK --}}
                            <div class="card border shadow-sm d-flex flex-column"
                                style="overflow: hidden; border-radius: 10px; max-height: 35vh; flex-shrink: 0;">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold uppercase text-dark">
                                        <i data-lucide="users" class="me-1 text-orange" style="width: 12px;"></i> Resume
                                        PIC Stock
                                    </span>
                                    <div class="d-flex justify-content-end align-items-center gap-1 flex-wrap">
                                        <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"
                                            id="btnRefreshData" style="font-size: 10px;" title="Refresh Data">
                                            <i data-lucide="refresh-cw" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            Refresh
                                        </button>

                                        <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"
                                            style="font-size: 10px;"
                                            onclick="window.open('{{ route('rekap.kso', ['so_name' => $selected_so]) }}', '_blank')">
                                            <i data-lucide="printer" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            Print Rekap KSO
                                        </button>

                                        <button class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;"
                                            data-bs-toggle="modal" data-bs-target="#modalPrintTagKSO">
                                            <i data-lucide="printer" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            Print KSO
                                        </button>

                                        <button class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;"
                                            data-bs-toggle="modal" data-bs-target="#modalUploadOracle">
                                            <i data-lucide="upload-cloud" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            Upload Oracle
                                        </button>

                                        <button class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;"
                                            type="button" id="btn-export-excel-appkso">
                                            <i data-lucide="file-spreadsheet" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            Export Excel
                                        </button>

                                        <button type="button"
                                            class="btn btn-xs fw-bold shadow-sm text-nowrap {{ (request()->route()->getName() ?? '') === 'live.progress' ? 'btn-warning text-dark' : 'btn-dark' }}"
                                            style="font-size: 10px;" onclick="openNavbarProgressModal()">
                                            <i data-lucide="activity" class="me-1"
                                                style="width: 12px; height: 12px;"></i>
                                            LIVE PROGRESS
                                        </button>


                                        {{-- <button type="button" id="btn-export-excel-appkso"
                                            class="btn btn-xs btn-succes fw-bold text-uppercase no-print"
                                            style="font-size: 10px; height: 26px; display: flex; align-items: center; gap: 4px; color: #000 !important;">
                                            <i data-lucide="file-spreadsheet" style="width: 12px; height: 12px;"></i>
                                            Export Excel
                                        </button> --}}

                                        {{-- Tambah di deretan tombol card-header Resume PIC Stock --}}
                                        <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"
                                            style="font-size: 10px;" id="btn-print-resume-pic"
                                            title="Print tabel Resume PIC Stock">
                                            <i data-lucide="printer" class="me-1" style="width: 12px; height: 12px;"></i>
                                            Print Resume PIC
                                        </button>
                                    </div>

                                </div>
                            </div>
                            <div class="table-responsive flex-grow-1 custom-scroll" style="overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" id="mainTable"
                                    style="font-size: 10px;">

                                    <thead class="table-dark sticky-top" style="z-index: 99;">
                                        <tr class="text-center">
                                            <th style="width: 5%;">NO</th>
                                            <th class="text-start">NAMA / OPERATOR</th>
                                            <th style="width: 15%;">TOTAL KSO</th> <!-- Kolom Baru -->
                                            <th style="width: 15%;">TOTAL SKU</th>
                                            <th class="text-end pe-3" style="width: 25%;">TOTAL QTY</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        @forelse ($resume_pic as $idx => $pic)
                                            {{-- Tambahkan class "clickable-row", data-opr, data-oprname, dan cursor: pointer --}}
                                            <tr class="clickable-row" data-opr="{{ $pic->opr }}"
                                                data-oprname="{{ $pic->oprname }}" style="cursor: pointer;"
                                                title="Klik untuk lihat detail scan">
                                                <td class="text-center text-muted border-end">{{ $idx + 1 }}</td>
                                                <td class="text-start fw-bold text-dark">
                                                    {{ strtoupper($pic->oprname) }} <span
                                                        class="text-muted">({{ $pic->opr }})</span>
                                                </td>
                                                <td class="text-center fw-bold text-primary">
                                                    {{ number_format($pic->total_kso) }} KSO
                                                </td>
                                                <td class="text-center fw-bold text-orange">
                                                    {{ number_format($pic->total_sku) }} SKU
                                                </td>
                                                <td class="text-end pe-3 fw-black text-dark">
                                                    {{ number_format($pic->total_qty) }} PCS
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Belum ada data
                                                    scan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center"
                                style="background-color: #1e293b; color: #fff; font-size: 9px; padding: 4px 6px; flex-shrink: 0;">
                                <span class="fw-bold" style="width: 5%;">
                                </span>
                                <span class="fw-bold text-white" style="width: 45%;">
                                    TOTAL OPERATOR : <span class="text-warning fw-black">{{ count($resume_pic) }}</span>
                                </span>
                                <span class="fw-bold text-info text-center" style="width: 19%;">
                                    {{ number_format($resume_pic->sum('total_kso')) }} KSO
                                </span>
                                <span class="fw-bold text-warning text-center" style="width: 20%;">
                                    {{ number_format($resume_pic->sum('total_sku')) }} SKU
                                </span>
                                <span class="fw-black text-white text-end pe-3" style="width: 27%;">
                                    {{ number_format($resume_pic->sum('total_qty')) }} PCS
                                </span>
                            </div>
                        </div>

                        {{-- 2. CARD DETAIL TRANSACTION LOG (Existing) --}}
                        <div class="card border shadow-sm d-flex flex-column flex-grow-1"
                            style="overflow: hidden; border-radius: 10px; min-height: 0;">
                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                <span class="small fw-bold uppercase text-dark"><i data-lucide="database"
                                        class="me-1 text-orange" style="width: 12px;"></i> Detail Transaction
                                </span>
                                <div class="input-group input-group-sm w-50">
                                    <span class="input-group-text bg-white border-end-0"><i data-lucide="search"
                                            class="text-muted" style="width: 14px; height: 14px;"></i></span>
                                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
                                        placeholder="Search anything...">
                                </div>
                            </div>

                            <div class="table-responsive flex-grow-1 custom-scroll" style="overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0" id="mainTable"
                                    style="font-size: 10px;">
                                    <thead class="table-dark sticky-top" style="z-index: 99;">
                                        <tr class="text-center">
                                            <th>NO</th>
                                            <th>DATE SHIFT</th>
                                            <th>OPR</th>
                                            <th class="text-start">OPR NAME</th>
                                            <th>NODOC</th>
                                            <th>ITEM CODE</th>
                                            <th class="text-start">DESCRIPTION</th>
                                            <th class="text-end pe-3">QTY STK</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody" class="bg-white">
                                        @foreach ($activities as $index => $act)
                                            <tr>
                                                <td class="text-center text-muted border-end">{{ $index + 1 }}</td>
                                                <td class="text-center fw-bold text-secondary">{{ $act->ydate_shift }}
                                                </td>
                                                <td class="text-center"><span
                                                        class="badge bg-light text-dark border fw-bold"
                                                        style="font-size: 8px;">{{ $act->opr }}</span></td>
                                                <td class="fw-semibold text-dark">{{ $act->oprname ?? '-' }}</td>
                                                <td class="text-center text-orange fw-bold">{{ $act->NoDoc }}</td>
                                                <td class="text-center fw-bold">{{ $act->ItemCode }}</td>
                                                <td class="text-start text-truncate" style="max-width: 180px;"
                                                    title="{{ $act->description }}">{{ $act->description }}</td>
                                                <td class="text-end pe-3 fw-black text-dark">
                                                    {{ number_format($act->QtyStk) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer py-1 bg-light small fw-bold text-muted d-flex justify-content-between">
                                <span>Rows: {{ number_format(count($activities)) }}</span>
                                <span>Status: <span class="text-success">Connected</span></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container-fluid fixed-wrapper">
        <!-- Existing content -->
        <div id="appkso-section" class="content-section active">
            <!-- ... existing appkso content ... -->
            @include('appkso.modal-print-tag')
            <!-- ADD PRINT BUTTON HERE (di atas tabel) -->
        </div>
    </div>

    {{-- MODAL UPLOAD ORACLE --}}
    <div class="modal fade" id="modalUploadOracle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">

                {{-- Header --}}
                <div class="modal-header py-2 px-3" style="background-color: #198754; color: white;">
                    <h6 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" style="font-size: 12px;">
                        <i data-lucide="upload-cloud" style="width: 14px; height: 14px;"></i>
                        Generate & Download Excel Upload Oracle
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body p-3">
                    <p class="text-muted mb-3" style="font-size: 11px;">
                        Pilih PIC / Operator untuk generate file <strong>.xlsm</strong> yang sudah berisi makro upload ke
                        Oracle NCA.
                    </p>

                    {{-- Filter PIC --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold text-uppercase mb-1" style="font-size: 10px; color: #198754;">
                            Pilih Operator (PIC)
                        </label>
                        <select id="selectOprUpload" class="form-select form-select-sm shadow-sm"
                            style="font-size: 11px; border-color: #198754;">
                            <option value="">-- Pilih PIC --</option>

                            {{-- Tambahkan ->sortBy('oprname') di sini supaya urut abjad nama --}}
                            @foreach ($resume_pic->sortBy('oprname') as $pic)
                                <option value="{{ $pic->opr }}" data-name="{{ $pic->oprname }}">
                                    {{ strtoupper($pic->oprname) }} ({{ $pic->opr }}) -
                                    ({{ number_format($pic->total_kso) }} KSO · {{ number_format($pic->total_qty) }} PCS)
                                </option>
                            @endforeach

                        </select>
                    </div>

                    {{-- Info Preview (muncul setelah pilih PIC) --}}
                    <div id="previewInfoUpload" class="d-none p-2 rounded mb-2"
                        style="background: rgba(25,135,84,0.07); border: 1px solid rgba(25,135,84,0.2); font-size: 11px;">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Operator</span>
                            <span class="fw-bold text-dark" id="previewOprName">-</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted">Kode PIC</span>
                            <span class="fw-bold" id="previewOprCode">-</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted">SO Aktif</span>
                            <span class="fw-bold text-success" id="previewSoName">{{ $selected_so }}</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer py-2 px-3 justify-content-between" style="background: #f8f9fa;">
                    <button type="button" class="btn btn-sm btn-light border fw-bold" data-bs-dismiss="modal"
                        style="font-size: 11px;">
                        Batal
                    </button>
                    <button type="button" id="btnDownloadXlsm" class="btn btn-sm fw-bold disabled"
                        style="font-size: 11px; background-color: #198754; color: white; min-width: 150px;"
                        onclick="downloadUploadOracleXlsm()">
                        <i data-lucide="download" style="width: 12px; height: 12px;"></i>
                        Download .xlsm
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL EXPORT EXCEL --}}
    <div class="modal fade" id="modal-export-excel-appkso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 360px;">
            <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">
                <div class="modal-header py-2 px-3" style="background-color: #fe6807; color: white;">
                    <h6 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" style="font-size: 12px;">
                        <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i>
                        Export Excel APPKSO
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label fw-bold text-uppercase mb-1" style="font-size: 10px; color: #fe6807;">
                            Tanggal Stock Opname
                        </label>
                        <input type="date" id="modal-tgl-so-export" class="form-control form-control-sm fw-bold"
                            style="font-size: 11px; border-color: #fe6807;">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-uppercase mb-1" style="font-size: 10px; color: #fe6807;">
                            Tanggal Posisi Stock
                        </label>
                        <input type="date" id="modal-tgl-posisi-export" class="form-control form-control-sm fw-bold"
                            style="font-size: 11px; border-color: #fe6807;">
                    </div>
                    <div id="modal-export-info" class="p-2 rounded mt-2"
                        style="background: rgba(254,104,7,0.06); border: 1px solid rgba(254,104,7,0.2); font-size: 11px;">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 justify-content-between" style="background: #f8f9fa;">
                    <button type="button" class="btn btn-sm btn-light border fw-bold" data-bs-dismiss="modal"
                        style="font-size: 11px;">Batal</button>
                    <button type="button" id="btn-do-export-excel" class="btn btn-sm fw-bold text-white"
                        style="font-size: 11px; background-color: #fe6807; border: none; min-width: 130px;">
                        <i data-lucide="download" style="width: 12px; height: 12px;"></i>
                        Download Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL RIWAYAT SCAN OPERATOR --}}
    <div class="modal fade" id="modalScanHistory" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold" id="modalTitleScanHistory">RIWAYAT SCAN OPERATOR</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 60vh;">
                        <table class="table table-hover table-bordered table-sm mb-0 align-middle text-nowrap"
                            style="font-size: 12px;">
                            <thead class="bg-light sticky-top text-dark text-center">
                                <tr>
                                    <th style="width: 5%;">NO</th>
                                    <th>OPR ID</th>
                                    <th>NAMA OPR</th>
                                    <th>NO KSO</th>
                                    <th>ITEM CODE</th>
                                    <th>DESKRIPSI</th>
                                    <th class="text-end text-primary pe-3" style="width: 10%;">QTY SCAN</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyScanHistory">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/appkso/appkso.js') }}"></script>
    <script src="{{ asset('js/appkso/modal-print-tag.js') }}"></script>
    <script src="{{ asset('js/excel.min.js') }}"></script>


    <script>
        window.picNoksoMap = @json($pic_nokso_map);
        window.printPreviewRoute = "{{ route('appkso.print-preview') }}";
        window.cachedAppksoDetail = @json($cachedDetail);
    </script>
    {{-- SWEETALERT & PROGRESS MODAL SCRIPT --}}
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        function openProgressModal() {
            let inputOptions = {};
            @foreach ($list_kso as $kso)
                inputOptions["{{ $kso->so_name }}"] = "{{ $kso->so_name }} — Counter: {{ $kso->def_counter ?? '-' }}";
            @endforeach

            Swal.fire({
                title: 'Pilih SO Aktif',
                html: '<div style="font-size: 12px; color: #555; margin-bottom: 8px;">Pilih Stock Opname yang ingin dipantau:</div>',
                input: 'select',
                inputOptions: inputOptions,
                inputValue: "{{ $selected_so }}",
                showCancelButton: true,
                confirmButtonText: 'Buka Live Progress',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#fe6807',
                cancelButtonColor: '#6c757d',
                inputAttributes: {
                    style: 'font-size: 13px; padding: 6px; border-radius: 6px;'
                },
                preConfirm: (selectedSo) => {
                    if (!selectedSo) {
                        Swal.showValidationMessage('Pilih salah satu SO dulu bro!');
                        return false;
                    }
                    return selectedSo;
                }
            }).then((result) => {
                if (result.value) { // ✅ hapus cek isConfirmed
                    window.location.href = '/auto-progress?so_name=' + encodeURIComponent(result.value.trim());
                }
            });
        }
    </script>
@endsection
