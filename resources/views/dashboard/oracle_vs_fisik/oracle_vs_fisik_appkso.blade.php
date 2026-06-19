<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    @media print {

        .no-print,
        nav,
        .sidebar,
        footer {
            display: none !important;
        }

        body {
            background: #fff !important;
            color: #000 !important;
            font-family: Arial, sans-serif;
        }

        .card {
            border: none !important;
            shadow: none !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000 !important;
            padding: 4px !important;
            /* font-size: 11px !important; */
        }

        thead {
            display: table-header-group !important;
            background-color: #f0f0f0 !important;
            color: #000 !important;
        }
    }
</style>

<div class="container-fluid p-0 d-flex flex-column gap-1 text-dark" style="font-size: 11px; mt-1;">

    {{-- ======================================================= --}}
    {{-- 🧱 BARIS 1 & 2: PANEL IMPOR (KIRI) & PANEL MONITORING (KANAN) --}}
    {{-- ======================================================= --}}
    <div class="row g-1 mt-1">

        {{-- 📥 CARD UPLOAD (col-xl-2) --}}
        <div class="col-12 col-xl-2 d-flex flex-column">
            <div class="card border border-warning shadow-sm p-3 text-center h-100 d-flex flex-column justify-content-center bg-white"
                style="border-radius: 12px; min-height: 250px;">
                <div class="p-2 rounded-circle bg-warning bg-opacity-10 mx-auto mb-2 d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px;">
                    <i data-lucide="file-spreadsheet" style="width: 22px; height: 22px; color: #fe6807;"></i>
                </div>

                <h6 class="text-uppercase fw-bold text-dark mb-1"
                    style="letter-spacing: 0.5px; font-weight: 900; font-size: 12px;">
                    Upload APPKSO scan
                </h6>
                <p class="text-muted mb-2" style="font-size: 10px; line-height: 12px;">
                    Unggah file <strong>.xlsx</strong> hasil opname APPKSO asli.
                </p>

                <form id="form-upload-appkso">
                    @csrf
                    <div class="text-start mb-2">
                        <select id="appkso-upload-warehouse" class="form-select form-select-sm fw-bold border-warning"
                            style="font-size: 11px;" required>
                            <option value="" disabled selected>-- PILIH GUDANG TUJUAN --</option>
                            <option value="APW">APW</option>
                            <option value="BPW">BPW</option>
                            <option value="DPW">DPW</option>
                            <option value="RPW">RPW</option>
                        </select>
                    </div>

                    <div class="border border-2 border-dashed rounded-3 p-3 bg-light position-relative mb-2"
                        style="border-color: #fe6807 !important;">
                        <input type="file" id="file-excel" name="file_excel"
                            class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;"
                            accept=".xlsx" required>
                        <i data-lucide="upload-cloud" class="text-muted mb-1" style="width: 20px; height: 20px;"></i>
                        <div class="small fw-bold text-secondary text-truncate px-2" id="text-file-excel"
                            style="font-size: 10px;">
                            Klik atau seret file Excel ke sini
                        </div>
                    </div>

                    <button type="submit"
                        class="btn w-100 btn-sm fw-bold py-2 rounded-pill shadow-sm text-uppercase text-white"
                        style="font-size: 11px; letter-spacing: 0.5px; background-color: #fe6807; border: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i data-lucide="file-check" style="width: 14px; height: 14px;"></i> Proses Import Data
                    </button>
                </form>
            </div>
        </div>

        {{-- SISI KANAN PANEL (col-xl-10) --}}
        <div class="col-12 col-xl-10">
            <div class="row g-1 align-content-start">

                {{-- 🛠️ 1. CARD BUTTON BARIS ATAS (col-12) --}}
                <div class="col-12 mb-0">
                    <div class="card border border-secondary shadow-sm p-2 bg-white d-flex flex-row align-items-center justify-content-between flex-wrap gap-1"
                        style="border-radius: 8px;">
                        <div class="d-flex align-items-center gap-2">
                            <select id="appkso-view-warehouse"
                                class="form-select form-select-sm fw-bold border-primary shadow-sm"
                                style="width: 200px; font-size: 11px; height: 28px;">
                                <option value="" selected>⏳ MEMUAT GUDANG...</option>
                            </select>
                            <button type="button" class="btn btn-xs btn-outline-dark fw-bold text-uppercase no-print"
                                onclick="refreshCurrentFisikPage();"
                                style="font-size: 10px; height: 26px; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i> Reset Data
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="btn-print-rekap-appkso"
                                class="btn btn-xs btn-success fw-bold text-uppercase no-print"
                                style="font-size: 10px; height: 26px; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="printer" style="width: 12px; height: 12px;"></i> Print Rekap
                            </button>

                            <button type="button" id="btn-print-rekap-kso"
                                class="btn btn-xs btn-primary fw-bold text-uppercase no-print"
                                style="font-size: 10px; height: 26px; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="printer" style="width: 12px; height: 12px;"></i> Print KSO
                            </button>
                            <button type="button" id="btn-export-excel-appkso"
                                class="btn btn-xs btn-warning fw-bold text-uppercase no-print"
                                style="font-size: 10px; height: 26px; display: flex; align-items: center; gap: 4px; color: #000 !important;">
                                <i data-lucide="file-spreadsheet" style="width: 12px; height: 12px;"></i> Export Excel
                            </button>

                        </div>
                    </div>
                </div>

                {{-- 📊 2. CARD DATA SNAPSHOT PATTERN (col-md-6) --}}
                <div class="col-12 col-md-6">
                    <div class="card border border-danger shadow-sm bg-white w-100 position-relative"
                        style="border-radius: 8px; height: 250px !important; min-height: 250px !important; max-height: 250px !important; overflow: hidden;">

                        <div
                            class="d-flex justify-content-between align-items-center border-bottom pb-1 pt-3 px-3 mb-2">
                            <h6 class="fw-bold text-uppercase text-danger mb-0"
                                style="font-size: 11px; display: flex; align-items: center; gap: 5px;">
                                <i data-lucide="boxes" style="width: 14px; height: 14px;"></i> Resume Pattern
                            </h6>
                            <div class="fw-bold text-secondary font-monospace" style="font-size: 10px;">
                                <span class="badge bg-secondary text-white px-2 mb-0 me-1">Total SKU: <span
                                        id="summary-pattern-sku">0</span></span>
                                <span class="badge bg-danger text-white px-2 mb-0">Total QTY: <span
                                        id="summary-pattern-qty">0</span></span>
                            </div>
                        </div>

                        <div class="table-responsive w-100 px-3"
                            style="overflow-y: auto; height: 165px !important; max-height: 185px !important;">
                            <table class="table table-sm table-striped align-middle text-center mb-0"
                                id="print-table-pattern" style="font-size: 10px; width:100%;">
                                <thead class="table-dark text-uppercase position-sticky top-0" style="z-index: 2;">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th class="text-start" width="52%">Pattern Size</th>
                                        <th width="20%">Total SKU</th>
                                        <th width="20%" class="text-end">Total QTY</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-appkso-pattern">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4 fw-bold">⏳ Silakan
                                            saring
                                            target gudang di panel filter atas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-start text-muted fst-italic position-absolute bottom-0 start-0 w-100 py-1 px-3 bg-light border-top"
                            style="font-size: 9px; letter-spacing: 0.3px; z-index: 10; border-radius: 0 0 8px 8px;">
                            <i data-lucide="info" style="width: 10px; height: 10px; vertical-align: middle;"
                                class="me-1"></i>Silahkan Klik Baris untuk melihat Detail Data
                        </div>
                    </div>
                </div>

                {{-- 📈 3. CARD RESUME AGREGASI PENUGASAN (col-md-6) --}}
                <div class="col-12 col-md-6">
                    <div class="card border border-success shadow-sm bg-white w-100 position-relative"
                        style="border-radius: 8px; height: 250px !important; min-height: 250px !important; max-height: 250px !important; overflow: hidden;">

                        <div
                            class="d-flex justify-content-between align-items-center border-bottom pb-1 pt-3 px-3 mb-2">
                            <h6 class="fw-bold text-uppercase text-success mb-0"
                                style="font-size: 11px; display: flex; align-items: center; gap: 5px;">
                                <i data-lucide="users" style="width: 14px; height: 14px;"></i> Resume PIC Stock
                            </h6>
                            <div class="fw-bold text-secondary font-monospace" style="font-size: 10px;">
                                <span class="badge bg-secondary text-white px-2 mb-0 me-1">Total Operator: <span
                                        id="summary-resume-operator">0</span></span>
                            </div>
                        </div>

                        <div class="table-responsive w-100 px-3"
                            style="overflow-y: auto; height: 165px !important; max-height: 165px !important;">
                            <table class="table table-sm table-striped align-middle text-center mb-0"
                                id="print-table-resume" style="font-size: 10px; width:100%;">
                                <thead class="table-dark text-uppercase position-sticky top-0" style="z-index: 2;">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th class="text-start" width="52%">Nama / Operator</th>
                                        <th width="20%">Total SKU</th>
                                        <th width="20%" class="text-end">Total QTY</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-appkso-resume">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4 fw-bold">⏳ Silakan
                                            saring target gudang di panel filter atas.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-start text-muted fst-italic position-absolute bottom-0 start-0 w-100 py-1 px-3 bg-light border-top"
                            style="font-size: 9px; letter-spacing: 0.3px; z-index: 10; border-radius: 0 0 8px 8px;">
                            <i data-lucide="info" style="width: 10px; height: 10px; vertical-align: middle;"
                                class="me-1"></i>Silahkan Klik Baris untuk melihat Detail Data
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- 🧱 BARIS 3: CARD DATA TABEL UTAMA FULL WIDTH (col-12)    --}}
    {{-- ======================================================= --}}
    <div class="row">
        <div class="col-12">
            <div class="card bg-white border border-primary shadow-sm p-3 d-flex flex-column"
                style="border-radius: 12px; overflow: hidden; height: 310px !important; min-height: 310px !important; max-height: 280px !important;">

                <div
                    class="d-flex justify-content-between align-items-center mb-2 flex-shrink-0 border-bottom pb-2 flex-wrap gap-2">
                    <h6 class="fw-bold text-uppercase mb-0 text-primary"
                        style="font-size: 12px; letter-spacing: 0.5px; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="table-properties" style="width: 14px; height: 14px;"></i> Detail Data
                    </h6>

                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm" style="width: 420px;">
                            <span class="input-group-text bg-light border-primary text-primary fw-bold"
                                style="font-size: 11px;"><i data-lucide="search"
                                    style="width: 12px; height: 12px;"></i></span>
                            <input type="text" id="search-table-global"
                                class="form-control form-control-sm fw-bold border-primary"
                                placeholder="Ketik No Penneng / Nama PIC Stock / No KSO / Item...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive"
                    style="overflow-y: auto; height: 250px !important; max-height: 250px !important;">
                    <table class="table table-sm table-hover align-middle mb-0" id="main-table-appkso-rows"
                        style="width: 100%;">
                        <thead
                            class="table-light position-sticky top-0 text-uppercase fw-bold border-bottom border-dark"
                            style="z-index: 5;">
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Warehouse</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Opr</th>
                                <th>Operator</th>
                                <th class="text-center">No KSO</th>
                                <th>Item</th>
                                <th>Deskripsi</th>
                                <th class="text-end">Qty</th>
                                <th>Verifikasi</th>
                                <th class="text-center">Tgl Verifikasi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-appkso">
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4 fw-bold">
                                    ⚠️ Silakan pilih saringan gudang di panel filter atas untuk memuat rekaman laporan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- 📥 MODAL POP-UP DRILL-DOWN (6 KOLOM) --}}
<div class="modal fade text-dark" id="modal-appkso-drilldown" static tabindex="-1" aria-hidden="true"
    style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-primary" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center"
                style="border-radius: 11px 11px 0 0;">
                <h6 class="modal-title fw-bold text-uppercase mb-0" id="modal-drilldown-title"
                    style="font-size: 12px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="layers" style="width: 14px; height: 14px;"></i> Detail Rekaman Data Breakdown
                </h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close" style="font-size: 10px;"></button>
            </div>
            <div class="modal-body p-2 bg-light">
                <div class="table-responsive bg-white rounded border shadow-sm"
                    style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 text-center"
                        style="font-size: 11px; width: 100%;">
                        <thead
                            class="table-light position-sticky top-0 text-uppercase fw-bold border-bottom border-dark"
                            style="z-index: 5;">
                            <tr>
                                <th width="5%">No</th>
                                <th width="8%">Opr</th>
                                <th class="text-start" width="22%">Nama / Operator</th>
                                <th width="15%">Item</th>
                                <th class="text-start" width="40%">Deskripsi</th>
                                <th class="text-end" width="10%">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-modal-drilldown"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-1 px-3 bg-white border-top d-flex justify-content-between">
                <div class="small fw-bold text-secondary font-monospace" style="font-size: 10px;">
                    Menampilkan: <span id="modal-total-rows" class="text-danger">0</span> Item SKU
                </div>
                {{-- <button type="button" class="btn btn-xs btn-secondary fw-bold text-uppercase rounded-pill px-3"
                    data-bs-dismiss="modal" style="font-size: 10px; height: 24px;">
                    Close
                </button> --}}
            </div>
        </div>
    </div>
</div>

{{-- 📥 MODAL GATEWAY PRINT FILTER MASTER --}}
<div class="modal fade text-dark" id="modal-print-filter-gateway" tabindex="-1" aria-hidden="true"
    style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-success" style="border-radius: 12px;">
            <div class="modal-header bg-success text-white py-2 px-3">
                <h6 class="modal-title fw-bold text-uppercase mb-0"
                    style="font-size: 12px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Setup Dokumen Cetak Rekap KSO
                </h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close" style="font-size: 10px;"></button>
            </div>
            <form id="form-trigger-print-rekap">
                <div class="modal-body p-3 bg-light" style="font-size: 11px;">

                    <div class="alert alert-warning border border-warning d-flex align-items-start gap-2 mb-3 shadow-sm"
                        style="font-size: 10px; line-height: 14px; border-radius: 6px;">
                        <i data-lucide="alert-triangle" class="text-warning flex-shrink-0"
                            style="width: 14px; height: 14px; mt-05;"></i>
                        <span class="text-dark fw-bold">PENTING: Gunakan Browser <span class="text-danger">MICROSOFT
                                EDGE</span> untuk proses cetak agar tatanan layout kertas, margin, dan KOP rekap Kartu
                            SO simetris presisi!</span>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1 text-secondary">SARING NAMA / OPERATOR SCAN</label>
                        <select id="modal-filter-print-opr" class="form-select form-select-sm fw-bold border-success"
                            style="font-size: 11px;" required></select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">TANGGAL STOCK OPNAME</label>
                            <input type="date" id="modal-filter-print-tgl-so"
                                class="form-control form-control-sm font-monospace fw-bold border-success" required
                                style="font-size: 11px;">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">TANGGAL POSISI STOCK</label>
                            <input type="date" id="modal-filter-print-tgl-posisi"
                                class="form-control form-control-sm font-monospace fw-bold border-success" required
                                style="font-size: 11px;">
                        </div>
                    </div>

                </div>
                <div class="modal-footer py-2 px-3 bg-white border-top">
                    <button type="button" class="btn btn-xs btn-secondary fw-bold rounded-pill px-3"
                        data-bs-dismiss="modal" style="font-size: 10px;">Batal</button>
                    <button type="submit" class="btn btn-xs btn-success fw-bold rounded-pill px-3"
                        style="font-size: 10px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="printer" style="width: 12px; height: 12px;"></i> Proses & Cetak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 📥 B. NEW COMPONENT: MODAL GATEWAY PRINT KSO BARCODE (KARTU FISIK POTONG GUNTING) --}}
<div class="modal fade text-dark" id="modal-print-kso-gateway" tabindex="-1" aria-hidden="true"
    style="z-index: 1080;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-primary" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold text-uppercase mb-0"
                    style="font-size: 12px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Setup Cetak Kartu Fisik KSO
                </h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close" style="font-size: 10px;"></button>
            </div>
            <form id="form-trigger-print-kso-cards">
                <div class="modal-body p-3 bg-light" style="font-size: 11px;">

                    <div class="alert alert-warning border border-warning d-flex align-items-start gap-2 mb-3 shadow-sm"
                        style="font-size: 10px; line-height: 14px; border-radius: 6px;">
                        <i data-lucide="alert-triangle" class="text-warning flex-shrink-0"
                            style="width: 14px; height: 14px;"></i>
                        <span class="text-dark fw-bold">PENTING: Gunakan Browser <span class="text-danger">MICROSOFT
                                EDGE</span> untuk proses print TAG KSO agar pembagian grid 4 kartu per halaman A4 pas
                            simetris!</span>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1 text-secondary">PILIH NAMA / OPERATOR PIC</label>
                        <select id="modal-kso-print-pic" class="form-select form-select-sm fw-bold border-primary"
                            style="font-size: 11px;" required>
                        </select>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">NO. DOC AWAL</label>
                            <select id="modal-kso-print-doc-from"
                                class="form-select form-select-sm fw-bold border-primary" style="font-size: 11px;"
                                required>
                                <option value="">⏳ Pilih PIC Dulu</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">NO. DOC AKHIR</label>
                            <select id="modal-kso-print-doc-to"
                                class="form-select form-select-sm fw-bold border-primary" style="font-size: 11px;"
                                required>
                                <option value="">⏳ Pilih PIC Dulu</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-bold mb-1 text-secondary">TANGGAL NOTA KARTU KSO</label>
                        <input type="date" id="modal-kso-print-tanggal-manual"
                            class="form-control form-control-sm fw-bold border-primary text-uppercase" required
                            style="font-size: 11px;">
                    </div>

                </div>
                <div class="modal-footer py-2 px-3 bg-white border-top">
                    <button type="button" class="btn btn-xs btn-secondary fw-bold rounded-pill px-3"
                        data-bs-dismiss="modal" style="font-size: 10px;">Batal</button>
                    <button type="submit" class="btn btn-xs btn-primary fw-bold rounded-pill px-3"
                        style="font-size: 10px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="printer" style="width: 12px; height: 12px;"></i> Proses & Print KSO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 📥 MODAL GATEWAY EXPORT EXCEL MULTI-SHEET --}}
<div class="modal fade text-dark" id="modal-export-excel-gateway" tabindex="-1" aria-hidden="true"
    style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-warning" style="border-radius: 12px;">
            <div class="modal-header py-2 px-3" style="background-color: #fe6807;">
                <h6 class="modal-title fw-bold text-uppercase text-white mb-0"
                    style="font-size: 12px; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i>
                    Setup Export Excel — Rekap APPKSO
                </h6>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"
                    aria-label="Close" style="font-size: 10px;"></button>
            </div>

            <form id="form-trigger-export-excel">
                <div class="modal-body p-3 bg-light" style="font-size: 11px;">

                    <div class="alert alert-info border border-info d-flex align-items-start gap-2 mb-3 shadow-sm"
                        style="font-size: 10px; line-height: 14px; border-radius: 6px;">
                        <i data-lucide="info" class="text-info flex-shrink-0" style="width: 14px; height: 14px;"></i>
                        <span class="text-dark fw-bold">
                            File Excel akan dibuat otomatis dengan <span class="text-danger">1 sheet per
                                operator</span>.
                            Nama sheet = nama PIC, isi = rekap kartu SO persis seperti Print Rekap.
                        </span>
                    </div>

                    {{-- Info jumlah sheet yang akan di-generate --}}
                    <div class="mb-3 p-2 bg-white rounded border border-warning text-center">
                        <span class="text-muted fw-bold" style="font-size: 10px;">Total Sheet yang akan dibuat:</span>
                        <span id="export-total-sheet-info" class="fw-bold text-danger ms-1"
                            style="font-size: 13px;">0 Sheet</span>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">TANGGAL STOCK OPNAME</label>
                            <input type="date" id="export-filter-tgl-so"
                                class="form-control form-control-sm font-monospace fw-bold border-warning" required
                                style="font-size: 11px;">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold mb-1 text-secondary">TANGGAL POSISI STOCK</label>
                            <input type="date" id="export-filter-tgl-posisi"
                                class="form-control form-control-sm font-monospace fw-bold border-warning" required
                                style="font-size: 11px;">
                        </div>
                    </div>

                </div>

                <div class="modal-footer py-2 px-3 bg-white border-top">
                    <button type="button" class="btn btn-xs btn-secondary fw-bold rounded-pill px-3"
                        data-bs-dismiss="modal" style="font-size: 10px;">Batal</button>
                    <button type="submit" id="btn-do-export-excel"
                        class="btn btn-xs fw-bold rounded-pill px-3 text-white"
                        style="font-size: 10px; background-color: #fe6807; border: none; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="download" style="width: 12px; height: 12px;"></i>
                        Generate & Download Excel
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            alert(
                "Metode Fitur print Seperti Ini tidak diizinkan,\n" +
                "Gunakan Tombol Print Rekap Pada Halaman WEB.\n\n" +
                "WAJIB !!!"
            );
        }
    });

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
