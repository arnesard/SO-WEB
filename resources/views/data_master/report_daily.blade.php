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
            height: calc(100vh - 80px);
            padding-bottom: 20px;
        }

        /* STYLE HEADER & TOMBOL */
        .header-container-gt {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #000000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-shrink: 0;
        }

        .btn-gt-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-weight: 700;
            font-size: 11px;
            padding: 8px 15px;
            text-transform: uppercase;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .active-report {
            background-color: #ffc107 !important;
            color: #000 !important;
            border-color: #000 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .active-master {
            background-color: #0d6efd !important;
            color: #fff !important;
            border-color: #000 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .active-monitoring {
            background-color: #198754 !important;
            color: #fff !important;
            border-color: #000 !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* STYLE KONTEN */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #ffc107;
            border-radius: 10px;
        }
    </style>

    <div class="container-fluid fixed-wrapper">
        {{-- 1. Header Card --}}
        <div class="header-container-gt">
            <div class="d-flex align-items-center gap-2">
                <button id="btn-report" onclick="showSection('report-section', 'btn-report')"
                    class="btn-gt-custom active-report">
                    <i class="fa-solid fa-sync-alt"></i> GT Report Daily Transaction
                </button>
                <button id="btn-monitoring" onclick="showSection('monitoring-section', 'btn-monitoring')"
                    class="btn-gt-custom">
                    <i class="fa-solid fa-barcode"></i> Monitoring Stock Barcode
                </button>
                <button id="btn-master" onclick="showSection('master-section', 'btn-master')" class="btn-gt-custom">
                    <i class="fa-solid fa-calendar-check"></i> Master Items
                </button>
            </div>
            <div class="text-end d-none d-md-block" style="border-left: 1px solid #dee2e6; padding-left: 20px;">
                <h6 class="fw-bold mb-0 text-primary uppercase" style="font-size: 11px;">Data Master</h6>
                <p class="text-muted mb-0 italic" style="font-size: 10px;">Gudang Ban B • v1.0</p>
            </div>
        </div>

        {{-- SECTION 1: REPORT DAILY --}}
        <div id="report-section" class="content-section active" style="height: calc(100vh - 170px); min-height: 500px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 1px solid #ffc107 !important; border-radius: 12px; overflow: hidden;">

                {{-- Header Card Utama --}}
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #ffc107; color: #000; border-radius: 11px 11px 0 0; flex-shrink: 0;">

                    <h5 class="card-title mb-0 small fw-bold text-uppercase" style="width: 50%;">
                        <i data-lucide="package" class="me-2" style="width: 16px; height: 16px;"></i>
                        Report Daily Transaction PT. Gadjah Tunggal Tbk (New)
                    </h5>

                    {{-- Tombol Download Template --}}
                    <div style="width: 20%; text-align: right;">
                        <a href="{{ asset('file_donwload/010526.txt') }}" download="Template_Report_Daily.txt"
                            class="btn btn-xs btn-dark fw-bold shadow-sm" style="font-size: 10px;">
                            <i data-lucide="download" class="me-1" style="width: 12px; height: 12px;"></i> Contoh Template
                            (.TXT)
                        </a>
                    </div>
                </div>

                {{-- Gunakan h-100 dan overflow hidden di body utama --}}
                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden;">
                    <div class="row g-3 h-100">

                        {{-- KOLOM KIRI (Upload & Calendar) --}}
                        <div class="col-md-3 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm h-100 d-flex flex-column"
                                style="border-radius: 10px; overflow: hidden;">
                                <div class="card-header bg-light py-2 text-center" style="flex-shrink: 0;">
                                    <h6 class="mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Status Transaksi
                                    </h6>
                                </div>

                                {{-- 1. Upload Panel --}}
                                <div class="p-1" style="flex-shrink: 0;">
                                    <div class="card shadow-sm border-0 mb-0"
                                        style="background-color: rgba(255, 193, 7, 0.05); border: 1px solid rgba(255, 193, 7, 0.582) !important; border-radius: 10px;">
                                        <div class="card-body p-2">
                                            <div class="mb-2 text-center">
                                                <label for="file_txt_daily"
                                                    class="form-label small fw-bold text-dark text-uppercase"
                                                    style="font-size: 9px;">
                                                    Pilih File Transaksi
                                                </label>
                                                <input type="file" id="file_txt_daily"
                                                    class="form-control form-control-sm"
                                                    style="border: 2px dashed #ffc107; background: #fff; font-size: 10px;"
                                                    onchange="previewTrxData(this)" accept=".txt">
                                            </div>
                                            <div class="text-center">
                                                <button id="btnSubmitTrx"
                                                    class="btn btn-success btn-xs fw-bold px-3 disabled"
                                                    style="border-radius: 8px; font-size: 10px;"
                                                    onclick="handleDailyUpload()">
                                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> UPLOAD DATA TXT
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. Area Kalender (Bisa scroll sendiri jika layar kecil) --}}
                                <div class="card-body p-2 overflow-auto custom-scroll flex-grow-1">
                                    <div
                                        class="d-flex justify-content-between align-items-center mb-1 bg-white border rounded p-1 shadow-sm">
                                        <button class="btn btn-xs py-0 px-2 btn-warning" onclick="changeMonth(-1)"><i
                                                data-lucide="chevron-left" size="14"></i></button>
                                        <div id="calMonthTitle" class="fw-bold small text-uppercase"
                                            style="font-size: 10px;"></div>
                                        <button class="btn btn-xs py-0 px-2 btn-warning" onclick="changeMonth(1)"><i
                                                data-lucide="chevron-right" size="14"></i></button>
                                    </div>

                                    <div class="row g-0 text-center mb-1 text-muted pb-1"
                                        style="font-size: 10px; font-weight:bold; border-bottom: 1px solid #dee2e6;">
                                        <div class="col">S</div>
                                        <div class="col">S</div>
                                        <div class="col">R</div>
                                        <div class="col">K</div>
                                        <div class="col">J</div>
                                        <div class="col">S</div>
                                        <div class="col text-danger">M</div>
                                    </div>
                                    <div id="calendarGrid" class="row g-0"></div>
                                    <div class="mt-0 border-top pt-2">
                                        {{-- Container Judul & Navigasi Sejajar --}}
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="fw-bold uppercase mb-0" style="font-size: 10px; color: #666;">
                                                <i data-lucide="pie-chart" class="me-1"
                                                    style="width: 12px; height: 12px;"></i> Resume Per Type
                                            </h6>

                                            {{-- Navigasi Pagination Kecil (Kanan) --}}
                                            <div id="summaryPagination" class="d-flex align-items-center gap-1 d-none">
                                                {{-- Tombol Kiri --}}
                                                <button
                                                    class="btn btn-xs btn-outline-secondary py-0 px-1 d-flex align-items-center justify-content-center"
                                                    onclick="prevSummaryPage()" style="height: 18px; width: 18px;">
                                                    <i data-lucide="chevron-left" style="width: 12px; height: 12px;"></i>
                                                </button>

                                                {{-- Info Halaman --}}
                                                <span id="summaryPageInfo" class="text-muted"
                                                    style="font-size: 9px; font-weight: bold; min-width: 35px; text-align: center; line-height: 18px;">
                                                    1 / 1
                                                </span>

                                                {{-- Tombol Kanan --}}
                                                <button
                                                    class="btn btn-xs btn-outline-secondary py-0 px-1 d-flex align-items-center justify-content-center"
                                                    onclick="nextSummaryPage()" style="height: 18px; width: 18px;">
                                                    <i data-lucide="chevron-right" style="width: 12px; height: 12px;"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Tabel Resume --}}
                                        <div class="table-responsive custom-scroll" style="min-height: 110px;">
                                            <table class="table table-sm table-bordered mb-0" style="font-size: 9px;">
                                                <thead class="table-light">
                                                    <tr class="text-center align-middle">
                                                        <th class="bg-dark text-white" style="width: 30%;">Type</th>
                                                        <th class="bg-success text-white" style="width: 30%;">OE Akhir
                                                        </th>
                                                        <th class="bg-primary text-white" style="width: 30%;">OK Akhir
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="summaryBody">
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">Pilih tanggal...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- TEXT BERJALAN --}}
                                <div class="flex-grow-1 mx-3" style="overflow: hidden; white-space: nowrap;">
                                    <marquee id="runningTextAlert" behavior="scroll" direction="left"
                                        class="fw-bold text-danger small" style="font-size: 11px;">
                                        {{-- Isi akan diupdate via JS --}}
                                    </marquee>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN (Data Preview) --}}
                        <div class="col-md-9 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm d-flex flex-column h-100" style="overflow: hidden;">

                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center"
                                    style="flex-shrink: 0;">
                                    <span class="small fw-bold uppercase">Detail Data Upload</span>
                                    <div class="input-group input-group-sm w-25">
                                        <span class="input-group-text bg-white border-end-0"><i data-lucide="search"
                                                style="width: 14px; height: 14px;"></i></span>
                                        <input type="text" id="searchData" class="form-control border-start-0 ps-0"
                                            placeholder="Search item..." onkeyup="filterTable()">
                                    </div>
                                </div>

                                {{-- BAGIAN TABEL: max-height menggunakan h-100 agar fleksibel --}}
                                <div class="table-responsive flex-grow-1 custom-scroll" style="overflow-y: auto;">
                                    <table class="table table-sm table-bordered table-hover mb-0"
                                        style="font-size: 10px; min-width: 1100px;">
                                        <thead class="table-dark text-center align-middle sticky-top"
                                            style="z-index: 99;">
                                            <tr>
                                                <th rowspan="2">No</th>
                                                <th rowspan="2">Item</th>
                                                <th rowspan="2">Desc</th>
                                                <th colspan="5" class="bg-success">OE</th>
                                                <th colspan="5" class="bg-primary">OK</th>
                                                <th colspan="5" class="bg-danger">ND</th>
                                            </tr>
                                            <tr style="font-size: 9px;">
                                                <th class="sticky-top" style="top: 24px;">Awal</th>
                                                <th class="sticky-top" style="top: 24px;">In</th>
                                                <th class="sticky-top" style="top: 24px;">Out</th>
                                                <th class="sticky-top" style="top: 24px;">Adj</th>
                                                <th class="sticky-top" style="top: 24px;">Akhir</th>
                                                <th class="sticky-top" style="top: 24px;">Awal</th>
                                                <th class="sticky-top" style="top: 24px;">In</th>
                                                <th class="sticky-top" style="top: 24px;">Out</th>
                                                <th class="sticky-top" style="top: 24px;">Adj</th>
                                                <th class="sticky-top" style="top: 24px;">Akhir</th>
                                                <th class="sticky-top" style="top: 24px;">Awal</th>
                                                <th class="sticky-top" style="top: 24px;">In</th>
                                                <th class="sticky-top" style="top: 24px;">Out</th>
                                                <th class="sticky-top" style="top: 24px;">Adj</th>
                                                <th class="sticky-top" style="top: 24px;">Akhir</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mainDisplayBody"></tbody>
                                    </table>
                                </div>

                                <div class="card-footer py-1 bg-light small fw-bold text-muted d-flex justify-content-between"
                                    style="flex-shrink: 0;">
                                    <span id="rowCountInfo">Total: 0 Data</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: MASTER DATA --}}
        <div id="master-section" class="content-section" style="height: calc(100vh - 170px); min-height: 500px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 1px solid #0d6efd !important; border-radius: 12px; overflow: hidden;">

                {{-- Header Master Data --}}
                <div class="card-header py-1 d-flex justify-content-between align-items-center"
                    style="background-color: #0d6efd; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0;">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase" style="width: 30%;">
                        <i data-lucide="database" class="me-2" style="width: 16px; height: 16px;"></i>
                        Master Data Item Gudang Ban B
                    </h5>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white border-0"><i data-lucide="search"
                                    style="width: 12px; height: 12px;"></i></span>
                            <input type="text" id="searchMaster" class="form-control border-0"
                                placeholder="Cari item..." onkeyup="filterMasterTable()">
                        </div>
                        <button class="btn btn-xs btn-light fw-bold shadow-sm" onclick="openAddModal()">
                            <i data-lucide="plus-circle" class="me-1" style="width: 12px; height: 12px;"></i> Tambah
                            Item
                        </button>
                    </div>
                </div>

                {{-- Body Tabel Master --}}
                <div class="card-body p-1 d-flex flex-column h-100" style="overflow: hidden;">
                    <div class="table-responsive flex-grow-1 custom-scroll" style="overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0" style="font-size: 11px;">
                            <thead class="table-dark sticky-top" style="z-index: 10;">
                                <tr class="text-center align-middle">
                                    <th>No</th>
                                    <th>Item Code</th>
                                    <th>Code Desc</th>
                                    <th>Description</th>
                                    <th>Grade</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Brand</th>
                                    <th>Category</th>
                                    <th style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="masterTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer Master --}}
                <div class="card-footer py-1 bg-light small fw-bold text-muted" style="flex-shrink: 0;">
                    <span id="masterRowCountInfo">Total: 0 Items</span>
                </div>
            </div>
        </div>
        {{-- MODAL MASTER DATA --}}
        <div class="modal fade" id="modalMasterItem" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-uppercase small" id="modalTitle">Tambah Item Baru</h5>

                    </div>
                    <form id="formMasterItem">
                        @csrf
                        <input type="hidden" id="item_id" name="id">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Item Code</label>
                                    <input type="text" name="item_code" id="m_item_code"
                                        class="form-control bg-light" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-danger">Item Code Desc (Unique)*</label>
                                    <input type="text" name="item_code_desc" id="m_item_code_desc"
                                        class="form-control bg-light" readonly required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Description (Report)</label>
                                    <input type="text" name="description" id="m_description"
                                        class="form-control bg-light" readonly>
                                </div>

                                <div class="col-md-1">
                                    <label class="form-label small fw-bold">Grade</label>
                                    <input type="text" name="grade" id="m_grade"
                                        class="form-control border-primary" list="list-grade"
                                        placeholder="Pilih/Ketik...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-primary">Product *</label>
                                    <input type="text" name="product" id="m_product"
                                        class="form-control border-primary" list="list-product"
                                        placeholder="Pilih/Ketik..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-primary">Type *</label>
                                    <input type="text" name="type" id="m_type"
                                        class="form-control border-primary" list="list-type" placeholder="Pilih/Ketik..."
                                        required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-primary">Brand *</label>
                                    <input type="text" name="brand" id="m_brand"
                                        class="form-control border-primary" list="list-brand"
                                        placeholder="Pilih/Ketik..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-primary">Category *</label>
                                    <input type="text" name="category" id="m_category"
                                        class="form-control border-primary" list="list-category"
                                        placeholder="Pilih/Ketik..." required>
                                </div>

                                <div class="modal-footer bg-light mt-4 px-0 pb-0 border-0">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold"
                                        id="btnSaveMaster">
                                        <i class="fa-solid fa-save me-1"></i> SIMPAN DATA MASTER
                                    </button>
                                </div>

                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- SECTION 3: MONITORING STOCK BARCODE --}}
        <div id="monitoring-section" class="content-section" style="height: calc(100vh - 170px); min-height: 500px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 1px solid #198754 !important; border-radius: 12px; overflow: hidden;">

                {{-- Header Card --}}
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #198754; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0;">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase" style="width: 30%;">
                        <i data-lucide="scan-barcode" class="me-2" style="width: 16px; height: 16px;"></i>
                        Monitoring Stock Barcode System
                    </h5>
                </div>

                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden;">
                    <div class="row g-3 h-100">
                        {{-- KOLOM KIRI --}}
                        <div class="col-md-3 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm h-100 d-flex flex-column"
                                style="border-radius: 10px; overflow: hidden;">
                                <div class="card-header bg-light py-2 text-center">
                                    <h6 class="mb-0 small fw-bold text-uppercase" style="font-size: 11px;">Upload &
                                        Periode</h6>
                                </div>

                                <div class="p-1" style="flex-shrink: 0;">
                                    <div class="card shadow-sm border-0 mb-0"
                                        style="background-color: rgba(25, 135, 84, 0.05); border: 1px solid #198754 !important;">
                                        <div class="card-body p-2">
                                            {{-- PILIH TANGGAL SEBELUM UPLOAD --}}
                                            <div class="mb-2">
                                                <label class="form-label small fw-bold text-uppercase mb-1"
                                                    style="font-size: 9px;">Tanggal Transaksi</label>
                                                <input type="date" id="upload_bc_date"
                                                    class="form-control form-control-sm mb-2" value="{{ date('Y-m-d') }}">

                                                <label class="form-label small fw-bold text-uppercase mb-1"
                                                    style="font-size: 9px;">File Barcode (CSV)</label>
                                                <input type="file" id="upload_barcode_file"
                                                    class="form-control form-control-sm"
                                                    style="border: 2px dashed #198754; font-size: 10px;">
                                            </div>
                                            <div class="text-center">
                                                <button id="btnUploadBC"
                                                    class="btn btn-success btn-xs fw-bold px-3 shadow-sm w-100"
                                                    onclick="uploadStockBarcode()" style="font-size: 10px;">
                                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> UPLOAD DATA
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Kalender --}}
                                <div class="card-body p-2 overflow-auto custom-scroll flex-grow-1">
                                    <div
                                        class="d-flex justify-content-between align-items-center mb-1 bg-white border rounded p-1 shadow-sm">
                                        <button class="btn btn-xs py-0 px-2 btn-success" onclick="changeMonthBC(-1)"><i
                                                data-lucide="chevron-left" size="14"></i></button>
                                        <div id="calMonthTitleBC" class="fw-bold small text-uppercase"
                                            style="font-size: 10px;"></div>
                                        <button class="btn btn-xs py-0 px-2 btn-success" onclick="changeMonthBC(1)"><i
                                                data-lucide="chevron-right" size="14"></i></button>
                                    </div>
                                    <div id="calendarGridBC" class="row g-0 text-center"></div>
                                </div>
                            </div>
                        </div>

                        {{-- KOLOM KANAN --}}
                        <div class="col-md-9 d-flex flex-column h-100" style="min-height: 0;">
                            <div class="card border shadow-sm d-flex flex-column h-100" style="overflow: hidden;">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <span id="bc_detail_title" class="small fw-bold text-uppercase text-success">Detail
                                        Data</span>
                                    <div class="input-group input-group-sm w-25">
                                        <input type="text" id="searchBarcode" class="form-control"
                                            placeholder="Cari Rak/Item..." onkeyup="filterBarcodeTable()">
                                    </div>
                                </div>
                                <div class="table-responsive flex-grow-1 custom-scroll">
                                    <table class="table table-sm table-bordered table-hover mb-0"
                                        style="font-size: 10px;">
                                        <thead class="table-dark text-center align-middle sticky-top">
                                            <tr>
                                                <th>No</th>
                                                <th>Rack</th>
                                                <th>Item</th>
                                                <th>W/C Week</th>
                                                <th>Qty</th>
                                                <th>QC</th>
                                                <th>QA</th>
                                                <th>QAA</th>
                                                <th>RND</th>
                                                <th>Holds</th>
                                                <th>OEM</th>
                                                <th>NG</th>
                                                <th>Booking</th>
                                                <th>Loc</th>
                                                <th>Hold Days</th>
                                            </tr>
                                        </thead>
                                        <tbody id="barcodeTableBody">
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">Silahkan klik
                                                    tanggal pada kalender untuk melihat detail data...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                {{-- Ganti bagian footer di KOLOM KANAN monitoring-section --}}
                                <div class="card-footer py-1 bg-light d-flex justify-content-between align-items-center"
                                    style="flex-shrink: 0;">
                                    <div class="small fw-bold text-muted">
                                        <span id="barcodeCountInfo">Total: 0 Rack</span>
                                    </div>
                                    {{-- AREA PAGINATION --}}
                                    <div id="bcPagination" class="d-flex gap-1 align-items-center d-none">
                                        <button class="btn btn-xs btn-outline-success py-0"
                                            onclick="changePageBC('prev')">Prev</button>
                                        <span id="bcPageInfo" class="fw-bold mx-2" style="font-size: 10px;">1 / 1</span>
                                        <button class="btn btn-xs btn-outline-success py-0"
                                            onclick="changePageBC('next')">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.appRoutes = {
            calendar: "{{ route('data_master.calendar_status') }}",
            data: "{{ route('data_master.daily_data') }}",
            upload: "{{ route('data_master.daily_upload') }}",
            summary: "{{ route('data_master.summary_data') }}",
            master_list: "{{ route('data_master.master_items') }}",
            check_missing: "{{ route('data_master.check_missing') }}"
        };
    </script>
    <script src="{{ asset('js/data_master/master_data.js') }}"></script>
    <script src="{{ asset('js/data_master/report_daily_transaction.js') }}"></script>
    <script src="{{ asset('js/data_master/upload_stock_barcode.js') }}"></script>
@endsection

{{-- DAFTAR PILIHAN OTOMATIS (DATALIST) --}}
<datalist id="list-grade">
    <option value="OE">
    <option value="OK">
</datalist>

<datalist id="list-product">
    <option value="TIRE">
    <option value="TUBE">
    <option value="VALVE">
    <option value="RIMBAND">
</datalist>

<datalist id="list-type">
    <option value="TUBELESS">
    <option value="TUBETYPE">
    <option value="-">
</datalist>

<datalist id="list-brand">
    <option value="IRC">
    <option value="GT">
    <option value="ZENEOS">
    <option value="-">
</datalist>

<datalist id="list-category">
    <option value="REGULER">
    <option value="SPAREPART">
    <option value="IMPORT">
    <option value="EXPORT">
    <option value="-">
</datalist>
