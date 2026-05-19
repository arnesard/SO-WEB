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
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #fe6807; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0;">

                    <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center" style="width: 50%;">
                        <i data-lucide="clipboard-check" class="me-2" style="width: 16px; height: 16px;"></i>
                        Pelaksanaan Stock Opname (APPKSO) - Gudang Ban B
                    </h5>

                    <div style="width: 50%; text-align: right;">
                        <button class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;"
                            onclick="exportToExcel()">
                            <i data-lucide="file-spreadsheet" class="me-1" style="width: 12px; height: 12px;"></i> Export
                            XLSX
                        </button>
                        <button class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;"
                            data-bs-toggle="modal" data-bs-target="#modalPrintTagKSO">
                            <i data-lucide="printer" class="me-1" style="width: 12px; height: 12px;"></i>
                            Print KSO
                        </button>
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
                                        <div
                                            class="p-2 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-muted">BPW 1</span>
                                            <span class="badge bg-dark">READY</span>
                                        </div>
                                        <div
                                            class="p-2 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-muted">BPW 2</span>
                                            <span class="badge bg-dark">READY</span>
                                        </div>
                                        <div
                                            class="p-2 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                                            <span class="small fw-bold text-muted">BPW 3</span>
                                            <span class="badge bg-dark">READY</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN (Tabel Detail Transaction) --}}
                        <div class="col-md-8 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm d-flex flex-column h-100"
                                style="overflow: hidden; border-radius: 10px;">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold uppercase text-dark"><i data-lucide="database"
                                            class="me-1 text-orange" style="width: 12px;"></i> Detail Transaction
                                        Log</span>
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
                                <div
                                    class="card-footer py-1 bg-light small fw-bold text-muted d-flex justify-content-between">
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
@endsection

<script src="{{ asset('js/appkso/appkso.js') }}"></script>
<script src="{{ asset('js/appkso/modal-print-tag.js') }}"></script>
<script>
    window.picNoksoMap = @json($pic_nokso_map);
    window.printPreviewRoute = "{{ route('appkso.print-preview') }}";
</script>
