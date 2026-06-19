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

        .bg-orange {
            background-color: #fe6807 !important;
        }

        .text-orange {
            color: #fe6807 !important;
        }

        .border-orange {
            border-color: #fe6807 !important;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #fe6807;
            border-radius: 10px;
        }
    </style>

    <div class="container-fluid fixed-wrapper">
        <div class="card shadow-sm border-0 d-flex flex-column h-100"
            style="border: 1px solid #fe6807 !important; border-radius: 12px; overflow: hidden;">

            {{-- 1. Header Card Utama --}}
            <div class="card-header py-2 d-flex justify-content-between align-items-center"
                style="background-color: #fe6807; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0; overflow: hidden;">

                {{-- Title: Dibuat flex-shrink-0 supaya tulisannya nggak kegencet --}}
                <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center flex-shrink-0 me-3">
                    <i data-lucide="clipboard-check" class="me-2" style="width: 16px; height: 16px;"></i>
                    SnapShot (Oracle)
                </h5>

                {{-- Wrapper Navbar: Pakai flex-grow-1 supaya memakan sisa layar dan mepet kanan otomatis --}}
                <div class="d-flex justify-content-end align-items-center flex-grow-1 ms-auto"
                    style="overflow-x: auto; scrollbar-width: none;">

                    {{-- Navbar Navigasi Modul --}}
                    @include('appkso.navbar')

                </div>
            </div>

            {{-- BODY --}}
            <div class="card-body d-flex flex-column flex-xl-row gap-3 p-3" style="overflow: hidden; flex: 1;">

                {{-- KIRI: UPLOAD FORM --}}
                <div class="flex-shrink-0 d-flex flex-column justify-content-center"
                    style="width: 100%; max-width: 360px; min-width: 300px;">
                    <div class="card border-0 shadow-sm p-4 text-center h-100 d-flex flex-column justify-content-center"
                        style="border-radius: 12px; background: #ffffff;">
                        <div class="p-3 rounded-circle bg-success bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 65px; height: 65px;">
                            <i data-lucide="file-up" style="width: 28px; height: 28px;" class="text-success"></i>
                        </div>
                        <h6 class="fw-black text-dark mb-1 text-uppercase" style="letter-spacing: 0.5px;">Upload Snapshot
                            oracle</h6>
                        <p class="text-muted mb-3" style="font-size: 11px; line-height: 14px;">
                            Unggah berkas <strong>.xlsx</strong> hasil tarikan data snapshot.<br>
                            Kolom: <code>ItemCode</code>, <code>QtyStk</code>
                        </p>

                        <form id="appkso-form-upload-snapshot" onsubmit="event.preventDefault();">
                            @csrf
                            <div class="text-start mb-3">
                                <label class="fw-bold mb-1 text-secondary" style="font-size: 11px;">Target SO Name</label>
                                <select id="appkso-upload-target-so" name="target_so"
                                    class="form-select form-select-sm fw-bold border-success" required>
                                    <option value="" disabled selected>⏳ Memuat SO Name...</option>
                                </select>
                            </div>

                            <div class="border border-2 border-dashed rounded-3 p-4 bg-light position-relative"
                                style="border-color: #28a745 !important; cursor: pointer;">
                                <input type="file" id="appkso-file-excel" name="file_excel"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;"
                                    accept=".xlsx, .xls" required>
                                <i data-lucide="upload-cloud" class="text-muted mb-2"
                                    style="width: 24px; height: 24px;"></i>
                                <div class="small fw-bold text-secondary text-truncate px-2" id="appkso-text-file-excel"
                                    style="font-size: 11px;">Klik atau seret file Excel ke sini</div>
                            </div>

                            <button type="submit"
                                class="btn btn-sm btn-success w-100 fw-bold mt-3 py-2 rounded-pill shadow-sm text-uppercase"
                                style="font-size: 11px; letter-spacing: 0.5px;">
                                <i data-lucide="cloud-upload" style="width: 13px; height: 13px;"></i> Proses Import Data
                            </button>
                        </form>
                    </div>
                </div>

                {{-- KANAN: TABEL DATA --}}
                <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm d-flex flex-column"
                    style="overflow: hidden;">

                    {{-- Filter Bar --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 flex-shrink-0">
                        <select id="appkso-filter-so" class="form-select form-select-sm fw-bold border-primary"
                            style="width: 220px;" onchange="window.loadAppksoSnapshotData()">
                            <option value="" selected>⏳ MEMUAT SO NAME...</option>
                        </select>

                        <div class="input-group input-group-sm" style="max-width: 420px; width: 100%;">
                            <span class="input-group-text bg-white border-end-0">
                                <i data-lucide="search" style="width: 13px; height: 13px;" class="text-muted"></i>
                            </span>
                            <input type="text" id="appkso-search-item"
                                class="form-control form-control-sm border-start-0 border-end-0 ps-0"
                                style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()"
                                placeholder="Cari item kode atau deskripsi...">
                            <button class="btn btn-primary fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                                type="button" onclick="window.loadAppksoSnapshotData()" style="font-size: 10px;">
                                <i data-lucide="search" style="width: 12px; height: 12px;"></i> CARI
                            </button>
                            <button class="btn btn-dark fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                                type="button"
                                onclick="document.getElementById('appkso-search-item').value=''; document.getElementById('appkso-filter-so').value=''; window.loadAppksoSnapshotData();"
                                style="font-size: 10px;">
                                <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i> RESET
                            </button>
                        </div>
                    </div>

                    {{-- Tabel --}}
                    <div class="flex-grow-1 position-relative" style="overflow: hidden;">
                        <div class="table-responsive custom-scroll"
                            style="overflow-y: auto; max-height: calc(100vh - 260px);">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 11px;">
                                <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                                    <tr>
                                        <th class="bg-light text-center" width="5%">No.</th>
                                        <th class="bg-light" width="25%">Item Code</th>
                                        <th class="bg-light" width="50%">Description (From Master)</th>
                                        <th class="bg-light text-end" width="20%">Qty Snapshot</th>
                                    </tr>
                                </thead>
                                <tbody id="appkso-tbody-snapshot">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            ⚠️ Silakan pilih SO Name terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light fw-bold text-uppercase position-sticky bottom-0"
                                    style="z-index: 5; box-shadow: inset 0 1px 0 #dee2e6;">
                                    <tr>
                                        <td colspan="3" class="text-end pe-3 text-secondary">
                                            Menampilkan: <span id="appkso-summary-total-rows"
                                                class="text-danger ms-1 font-monospace">0</span> ITEM SKU
                                        </td>
                                        <td class="text-end text-primary font-monospace" id="appkso-summary-total-qty"
                                            style="font-size: 13px;">0 PCS</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/appkso/snapshot.js') }}"></script>
    @endpush
@endsection
