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

        /* --- HEADER & NAVIGATION STYLE --- */
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

        /* State Warna Aktif Navigasi Atas */
        .active-oracle-barcode {
            background-color: #191bdf !important;
            color: #fff !important;
            border-color: #000 !important;
        }

        .active-oracle-fisik {
            background-color: #fe6807 !important;
            color: #fff !important;
            border-color: #000 !important;
        }

        .active-triple-match {
            background-color: #09080d !important;
            color: #fff !important;
            border-color: #000 !important;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* --- STYLING DIAGRAM PREMIUM (Point 1 & 2) --- */
        .icon-box-premium {
            background: white;
            width: 100px;
            height: 100px;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid #e2e8f0;
            position: relative;
            z-index: 10;
        }

        .icon-box-premium.main-acc {
            width: 150px;
            height: 110px;
            border: 4px solid #198754;
            box-shadow: 0 0 30px rgba(25, 135, 84, 0.3);
        }

        .icon-box-premium i {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .icon-box-premium .label {
            font-size: 12px;
            font-weight: 900;
            /* Point 1: Tulisan Jelas */
            color: #1a202c !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Animasi Garis Belok (Point 2) */
        .path-container {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        /* SVG untuk membuat garis animasi yang bisa belok */
        .flow-svg {
            position: absolute;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 400px;
            height: 150px;
            z-index: 1;
        }

        .flow-path {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 3;
            stroke-linecap: round;
        }

        .flow-dash {
            fill: none;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-dasharray: 20, 100;
            /* Membuat potongan cahaya */
            animation: dashMove 3s infinite linear;
        }

        @keyframes dashMove {
            to {
                stroke-dashoffset: -120;
            }
        }

        /* --- CARD INSTRUKSI STYLE (Point 3) --- */
        .instruction-card {
            background: #ffffff;
            border: 2px solid #198754;
            padding: 30px 20px;
            border-radius: 20px;
            text-align: center;
            height: 100%;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .clickable-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Point 3: Warna tombol langsung terlihat */
        .btn-access-ovb {
            background-color: #191bdf;
            color: white;
            border: none;
        }

        .btn-access-ovf {
            background-color: #fe6807;
            color: white;
            border: none;
        }

        .btn-access-triple {
            background-color: #09080d;
            color: white;
            border: none;
        }

        .btn-access {
            font-size: 11px;
            font-weight: 800;
            padding: 10px 25px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-access:hover {
            opacity: 1 !important;
            transform: scale(1.05);
            color: #000000 !important;
            /* Tulisan jadi Hitam pekat */
            background-color: #ffffff !important;
            /* Background putih biar kontras */
            border: 2px solid #000000 !important;
            /* Border hitam tegas */
            filter: brightness(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-weight: 900;
            /* Tebalin dikit biar makin jelas */
        }

        .pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid #198754;
            border-radius: 20px;
            animation: ripple 2s infinite;
            opacity: 0;
        }

        /* Border untuk card KIRI (Oracle Vs Barcode) */
        .card-ovb:hover {
            border: 2px solid #191bdf !important;
        }

        /* Border untuk card TENGAH (Oracle Vs Fisik) */
        .card-ovf:hover {
            border: 2px solid #fe6807 !important;
        }

        /* Border untuk card KANAN (Triple Match) */
        .card-triple:hover {
            border: 2px solid #09080d !important;
        }

        /* Border Tabel Summary - Kita bikin kontras tinggi */
        .table-hover-custom {
            border: 1px solid #999 !important;
            border-collapse: collapse !important;
        }

        /* Paksa garis muncul di header dan cell body */
        .table-hover-custom thead th,
        .table-hover-custom tbody td {
            border: 1px solid #bbb !important;
        }

        /* Efek Hover Baris: Gunakan selektor super spesifik */
        .table-hover-custom tbody tr:hover td {
            background-color: #e7da47 !important;
            /* Kuning stabilo */
            color: #000 !important;
            /* Teks hitam biar jelas */
            transition: background-color 0.1s ease;
            cursor: pointer;
        }

        /* Efek Kedap Kedip Gila */
        .blink-me {
            animation: blinker 5.5s linear infinite;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        @keyframes blinker {
            50% {
                opacity: 0.1;
            }
        }

        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 0.5;
            }

            100% {
                transform: scale(1.3);
                opacity: 0;
            }
        }

        .hover-float {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }
    </style>

    <div class="container-fluid fixed-wrapper">
        {{-- 1. HEADER NAVIGASI --}}
        {{-- <div id="header-nav" class="header-container-gt"> --}}
        {{-- backup untuk aktif tampilan --}}
        {{-- <div id="header-nav" class="header-container-gt d-none">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button id="btn-ovb" onclick="switchDashboard('section-oracle-barcode', this, 'active-oracle-barcode')"
                        class="btn-gt-custom">
                        <i class="fa-solid fa-sync-alt"></i> Oracle Vs Barcode
                    </button>
                    <button id="btn-ovf" onclick="switchDashboard('section-oracle-fisik', this, 'active-oracle-fisik')"
                        class="btn-gt-custom">
                        <i class="fa-solid fa-barcode"></i> Oracle Vs Fisik
                    </button>
                    <button id="btn-triple" onclick="switchDashboard('section-triple-match', this, 'active-triple-match')"
                        class="btn-gt-custom">
                        <i class="fa-solid fa-calendar-check"></i> Oracle Vs Barcode Vs Fisik
                    </button>
                </div>
            </div>
            <div class="text-end">
                <h6 class="fw-bold mb-0 text-primary uppercase" style="font-size: 11px;">System Integrity</h6>
                <p class="text-muted mb-0 italic" style="font-size: 10px;">Gudang Ban B • v1.0</p>
            </div>
        </div> --}}

        {{-- backup untuk aktif tampilan --}}
        {{-- <div id="section-default" class="content-section active"> --}}
        {{-- 2. MAIN CONTENT --}}
        <div id="section-default" class="content-section">
            <div class="flex-grow-1 d-flex align-items-center justify-content-center"
                style="background: radial-gradient(circle at center, #ffffff 0%, #f1f4f9 100%); border-radius: 20px;">
                <div class="text-center p-4" style="max-width: 1000px; width: 100%;">

                    {{-- DIAGRAM FLOW (Point 1 & 2) --}}
                    <div class="position-relative mb-5" style="height: 280px;">
                        {{-- Row 1: Pillars --}}
                        <div class="d-flex justify-content-center align-items-center gap-5 position-relative"
                            style="z-index: 10;">
                            <div class="icon-box-premium shadow-sm hover-float" style="border-top: 5px solid #191bdf;">
                                <i class="fa-solid fa-database" style="color: #191bdf;"></i>
                                <span class="label">ORACLE</span>
                            </div>
                            <div class="icon-box-premium shadow-sm hover-float"
                                style="border-top: 5px solid #333; animation-delay: 0.2s;">
                                <i class="fa-solid fa-barcode" style="color: #333;"></i>
                                <span class="label">BARCODE</span>
                            </div>
                            <div class="icon-box-premium shadow-sm hover-float"
                                style="border-top: 5px solid #fe6807; animation-delay: 0.4s;">
                                <i class="fa-solid fa-boxes-stacked" style="color: #fe6807;"></i>
                                <span class="label">FISIK</span>
                            </div>
                        </div>

                        {{-- ANIMASI GARIS BELOK MENGGUNAKAN SVG (Point 2) --}}
                        <svg class="flow-svg" viewBox="0 0 400 150">
                            <path class="flow-path" d="M 50 0 V 60 H 200" />
                            <path class="flow-dash" d="M 50 0 V 60 H 200" stroke="#191bdf" />

                            <path class="flow-path" d="M 200 0 V 100" />
                            <path class="flow-dash" d="M 200 0 V 100" stroke="#333" style="animation-delay: 0.5s;" />

                            <path class="flow-path" d="M 350 0 V 60 H 200" />
                            <path class="flow-dash" d="M 350 0 V 60 H 200" stroke="#fe6807" style="animation-delay: 1s;" />

                            <path class="flow-path" d="M 200 60 V 100" />
                            <path class="flow-dash" d="M 200 60 V 100" stroke="#198754" style="animation-duration: 1.5s;" />
                        </svg>

                        {{-- Row 2: Accuracy Target --}}
                        <div class="position-absolute w-100" style="bottom: 0;">
                            <div class="d-flex justify-content-center">
                                <div class="icon-box-premium main-acc shadow hover-float" style="animation-delay: 0.6s;">
                                    <div class="pulse-ring"></div>
                                    <i class="fa-solid fa-shield-check"></i>
                                    <span class="label">ACCURACY</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h2 class="fw-black text-dark mb-4">Stock Accuracy Control Center</h2>

                    {{-- 3 Card Utama (Point 3) --}}
                    <div class="row g-4 justify-content-center">
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-ovb" onclick="triggerNav('btn-ovb')">
                                <div>
                                    <h6 class="fw-bold">Stock Oracle Vs Barcode</h6>
                                    <p class="small">Validasi keselarasan data stok antara Oracle dengan hasil aktual
                                        scanning barcode.</p>
                                </div>
                                <button class="btn btn-access btn-access-ovb"
                                    onclick="switchDashboard('section-oracle-barcode', this, 'active-oracle-barcode')">Akses
                                    Modul</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-ovf" onclick="triggerNav('btn-ovf')">
                                <div>
                                    <h6 class="fw-bold">Stock Oracle vs Aktual Fisik</h6>
                                    <p class="small">Analisa selisih data Oracle terhadap jumlah stok fisik di area
                                        penyimpanan.</p>
                                </div>
                                <button class="btn btn-access btn-access-ovf"
                                    onclick="switchDashboard('section-oracle-fisik', this, 'active-oracle-fisik')">Akses
                                    Modul</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-triple" onclick="triggerNav('btn-triple')">
                                <div>
                                    <h6 class="fw-bold">Oracle Vs Barcode Vs Fisik</h6>
                                    <p class="small">Sinkronisasi tiga arah untuk memastikan integritas data tertinggi.</p>
                                </div>
                                <button class="btn btn-access btn-access-triple"
                                    onclick="switchDashboard('section-triple-match', this, 'active-triple-match')">Akses
                                    Modul</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- AREA CONTENT LAINNYA --}}

        {{-- 1. SECTION: ORACLE VS BARCODE --}}
        <div id="section-oracle-barcode" class="content-section active"
            style="height: calc(100vh - 170px); min-height: 550px;">
            {{-- Container Utama --}}
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 2px solid #191bdf !important; border-radius: 12px; overflow: hidden;">

                {{-- 1. Header Card Utama --}}
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #191bdf; color: #fff; flex-shrink: 0;">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center">
                        <i data-lucide="bar-chart-3" class="me-2 fa-spin-hover" style="width: 18px; height: 18px;"></i>
                        Dashboard Stock Oracle Vs Stock Barcode
                    </h5>
                    <a href="{{ route('dashboard.index') }}"
                        class="btn btn-xs btn-light fw-bold shadow-sm d-flex align-items-center"
                        style="font-size: 10px; text-decoration: none;">
                        <i data-lucide="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i>
                        DASHBOARD
                    </a>
                </div>

                {{-- 2. Body Utama --}}
                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden; background-color: #f8f9fa;">
                    <div class="row g-3 h-100">
                        {{-- Panggil Card Kiri --}}
                        @include('dashboard.oracle_vs_barcode.card_kiri')

                        {{-- Panggil Card Kanan --}}
                        @include('dashboard.oracle_vs_barcode.card_kanan')
                    </div>
                </div>
            </div>
        </div>
        {{-- modal --}}
        <div class="modal fade" id="modalDetailPattern" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="overflow: hidden;">
                    <div class="modal-header bg-dark text-white py-2 px-3">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center gap-3">
                                <h6 class="modal-title small fw-bold mb-0">
                                    <i data-lucide="list-collapse" class="me-2" style="width:16px;"></i>
                                    PATTERN: <span id="modalTitlePattern" class="text-warning"></span>
                                </h6>
                            </div>
                            <div id="sku-stats" class="d-flex align-items-center gap-2">
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-3 bg-light overflow-hidden d-flex flex-column">
                        <div id="modalDetailContent" class="h-100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- MODAL LEVEL 2: LOCATION DETAIL (Tambahin Tombol di Sini) --}}
        <div class="modal fade" id="modalDeepDetail" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title small fw-bold mb-0">Location Detail: <span id="deepDetailTitle"></span>
                        </h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="small text-muted fw-bold italic" style="font-size: 10px;">
                                <i class="fa-solid fa-circle-info me-1"></i> *Data Aplikasi Desktop Barcode - Monitoring
                                Stock
                            </span>
                            <button id="btnExportDeepExcel" class="btn btn-success btn-xs fw-bold px-3 shadow-sm">
                                <i class="fa-solid fa-file-excel me-1"></i> EXPORT TO EXCEL
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-white sticky-top">
                                    <tr class="text-center small">
                                        <th>NO</th>
                                        <th>LOC</th>
                                        <th>RACK</th>
                                        <th>ITEM CODE</th>
                                        <th>DESCRIPTION</th>
                                        <th>QTY</th>
                                    </tr>
                                </thead>
                                <tbody id="deepDetailTableBody" class="small"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. SECTION: ORACLE VS FISIK --}}
        <div id="section-oracle-fisik" class="content-section">
            <div class="flex-grow-1 d-flex align-items-center justify-content-center bg-white shadow-sm border rounded-4"
                style="border-color: #fe6807 !important; border-width: 2px;">
                <div class="text-center p-5">
                    <i class="fa-solid fa-boxes-stacked mb-3" style="font-size: 60px; color: #fe6807;"></i>
                    <h4 class="fw-bold" style="color: #fe6807;">Oracle Vs Aktual Fisik Verification</h4>
                    <p class="text-muted small">Analisa selisih stok sistem vs perhitungan gudang aktual.</p>
                    <hr class="my-4">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active fw-bold' : '' }}"
                            href="{{ route('dashboard.index') }}">
                            <i data-lucide="layout-dashboard" size="18"></i> Kembali Dashboard
                        </a>
                    </li>
                </div>
            </div>
        </div>

        {{-- 3. SECTION: TRIPLE MATCH (ORACLE VS BARCODE VS FISIK) --}}
        <div id="section-triple-match" class="content-section">
            <div class="flex-grow-1 d-flex align-items-center justify-content-center bg-white shadow-sm border rounded-4"
                style="border-color: #09080d !important; border-width: 2px;">
                <div class="text-center p-5">
                    <i class="fa-solid fa-shield-halved mb-3" style="font-size: 60px; color: #09080d;"></i>
                    <h4 class="fw-bold" style="color: #09080d;">Triple Match Integrity System</h4>
                    <p class="text-muted small">Sinkronisasi tiga arah: Oracle System, Barcode Scan, & Aktual Fisik.</p>
                    <hr class="my-4">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active fw-bold' : '' }}"
                            href="{{ route('dashboard.index') }}">
                            <i data-lucide="layout-dashboard" size="18"></i> Kembali Dashboard
                        </a>
                    </li>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Fungsi memunculkan header dan klik navigasi (Triggered by cards)
        function triggerNav(targetBtnId) {
            const nav = document.getElementById('header-nav');
            if (nav) {
                nav.classList.remove('d-none');
            }
            const btn = document.getElementById(targetBtnId);
            if (btn) {
                btn.click();
            }
        }

        // Logika pindah Tab/Section
        function switchDashboard(sectionId, btnElement, activeClass) {
            // Sembunyikan semua section
            document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));

            // Tampilkan section target
            const targetSection = document.getElementById(sectionId);
            targetSection.classList.add('active');

            // Reset state tombol navigasi atas
            document.querySelectorAll('.btn-gt-custom').forEach(btn => {
                btn.classList.remove('active-oracle-barcode', 'active-oracle-fisik', 'active-triple-match');
            });

            // Aktifkan tombol yang dipilih
            btnElement.classList.add(activeClass);

            // KUNCI PERBAIKAN: Jika masuk ke seksi grafik, paksa ECharts hitung ulang lebar
            if (sectionId === 'section-oracle-barcode') {
                setTimeout(() => {
                    if (myChart) {
                        myChart.resize();
                    }
                }, 300); // Kasih jeda dikit biar transisinya selesai
            }
        }
    </script>

    {{-- Letakkan ini tepat sebelum import script JS lu --}}
    <script>
        window.oracleDates = @json($oracleDates ?? []);
        window.barcodeDates = @json($barcodeDates ?? []);
        window.activeDates = @json($activeDates ?? []);
    </script>
    <script src="{{ asset('js/dashboard/oracle_vs_barcode.js') }}"></script>
@endsection
