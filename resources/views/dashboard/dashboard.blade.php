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
            height: calc(94vh - 20px);
            padding-bottom: 20px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        /* --- STYLING DIAGRAM PREMIUM --- */
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
            color: #1a202c !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Animasi Garis Belok */
        .path-container {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }

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
            animation: dashMove 3s infinite linear;
        }

        @keyframes dashMove {
            to {
                stroke-dashoffset: -120;
            }
        }

        /* --- CARD INSTRUKSI STYLE --- */
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
            background-color: #ffffff !important;
            border: 2px solid #000000 !important;
            filter: brightness(1.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            font-weight: 900;
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

        .card-ovb:hover {
            border: 2px solid #191bdf !important;
        }

        .card-ovf:hover {
            border: 2px solid #fe6807 !important;
        }

        .card-triple:hover {
            border: 2px solid #09080d !important;
        }

        .table-hover-custom {
            border: 1px solid #999 !important;
            border-collapse: collapse !important;
        }

        .table-hover-custom thead th,
        .table-hover-custom tbody td {
            border: 1px solid #bbb !important;
        }

        .table-hover-custom tbody tr:hover td {
            background-color: #e7da47 !important;
            color: #000 !important;
            transition: background-color 0.1s ease;
            cursor: pointer;
        }

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
        {{-- 1. MAIN DEFAULT LANDING SCREEN --}}
        <div id="section-default" class="content-section active">
            <div class="flex-grow-1 d-flex align-items-center justify-content-center"
                style="background: radial-gradient(circle at center, #ffffff 0%, #f1f4f9 100%); border-radius: 20px;">
                <div class="text-center p-4" style="max-width: 1000px; width: 100%;">

                    {{-- DIAGRAM FLOW --}}
                    <div class="position-relative mb-5" style="height: 280px;">
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

                    {{-- 3-CARD DASHBOARD MENU UTAMA --}}
                    <div class="row g-4 justify-content-center">
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-ovb">
                                <div>
                                    <h6 class="fw-bold">Stock Oracle Vs Barcode</h6>
                                    <p class="small">Validasi keselarasan data stok antara Oracle dengan hasil aktual
                                        scanning barcode.</p>
                                </div>
                                <button class="btn btn-access btn-access-ovb"
                                    onclick="switchDashboard('section-oracle-barcode', null, 'active-oracle-barcode')">Akses
                                    Modul</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-ovf">
                                <div>
                                    <h6 class="fw-bold">Stock Oracle vs Aktual Fisik</h6>
                                    <p class="small">Analisa selisih data Oracle terhadap jumlah stok fisik di area
                                        penyimpanan.</p>
                                </div>
                                <button class="btn btn-access btn-access-ovf"
                                    onclick="switchDashboard('section-oracle-fisik', null, 'active-oracle-fisik')">Akses
                                    Modul</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="instruction-card clickable-card card-triple">
                                <div>
                                    <h6 class="fw-bold">Oracle Vs Barcode Vs Fisik</h6>
                                    <p class="small">Sinkronisasi tiga arah untuk memastikan integritas data tertinggi.</p>
                                </div>
                                <button class="btn btn-access btn-access-triple"
                                    onclick="switchDashboard('section-triple-match', null, 'active-triple-match')">Akses
                                    Modul</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. AREA CONTENT SUB-SECTION DINAMIS --}}

        {{-- 2.1. SECTION: ORACLE VS BARCODE --}}
        <div id="section-oracle-barcode" class="content-section" style="height: calc(100vh - 50px); min-height: 550px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 2px solid #191bdf !important; border-radius: 12px; overflow: hidden;">
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #191bdf; color: #fff; flex-shrink: 0;">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center">
                        <i data-lucide="bar-chart-3" class="me-2" style="width: 18px; height: 18px;"></i>
                        Dashboard Stock Oracle Vs Stock Barcode
                    </h5>
                    <a href="{{ route('dashboard.index') }}" onclick="localStorage.clear();"
                        class="btn btn-xs btn-light fw-bold shadow-sm d-flex align-items-center"
                        style="font-size: 10px; text-decoration: none;">
                        <i data-lucide="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> DASHBOARD UTAMA
                    </a>
                </div>
                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden; background-color: #f8f9fa;">
                    <div class="row g-3 h-100">
                        @include('dashboard.oracle_vs_barcode.card_kiri')
                        @include('dashboard.oracle_vs_barcode.card_kanan')
                    </div>
                </div>
            </div>
        </div>

        {{-- 2.2. SECTION: ORACLE VS FISIK --}}
        <div id="section-oracle-fisik" class="content-section" style="height: calc(100vh - 50px); min-height: 550px;">
            <div class="card shadow-sm border-0 d-flex flex-column h-100"
                style="border: 2px solid #fe6807 !important; border-radius: 12px; overflow: hidden;">
                <div class="card-header py-2 d-flex justify-content-between align-items-center"
                    style="background-color: #fe6807; color: #fff; flex-shrink: 0;">
                    <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center">
                        <i data-lucide="monitor-check" class="me-2" style="width: 18px; height: 18px;"></i>
                        Stock Oracle Vs Aktual Fisik
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group me-2">
                            <button id="btn-master_size" onclick="switchFisikContent('master_size')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="ruler" class="me-1" style="width: 12px; height: 12px;"></i> MASTER SIZE
                            </button>
                            <button id="btn-pic" onclick="switchFisikContent('pic')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="user" class="me-1" style="width: 12px; height: 12px;"></i> MASTER PIC
                            </button>
                            <button id="btn-barcode_monitoring" onclick="switchFisikContent('barcode_monitoring')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="barcode" class="me-1" style="width: 12px; height: 12px;"></i> BARCODE
                            </button>
                            <button id="btn-tagstock" onclick="switchFisikContent('tagstock')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="file" class="me-1" style="width: 12px; height: 12px;"></i> TAG STOCK
                            </button>
                            <button id="btn-appkso" onclick="switchFisikContent('appkso')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="scan" class="me-1" style="width: 12px; height: 12px;"></i> APPKSO
                            </button>
                            <button id="btn-oracle_snapshot" onclick="switchFisikContent('oracle_snapshot')"
                                class="btn btn-xs btn-outline-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="camera" class="me-1" style="width: 12px; height: 12px;"></i> SNAPSHOT
                            </button>
                            {{-- <a href="{{ route('appkso.index') }}"
                                class="btn btn-xs btn-outline-light fw-bold shadow-sm d-flex align-items-center"
                                style="font-size: 9px; border-width: 1.5px; text-decoration: none;">
                                <i data-lucide="scan" class="me-1" style="width: 12px; height: 12px;"></i> APPKSO AUTO
                                BPW
                            </a> --}}
                            <button id="btn-default" onclick="switchFisikContent('default')"
                                class="btn btn-xs btn-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">
                                <i data-lucide="layout" class="me-1" style="width: 12px; height: 12px;"></i> DASHBOARD
                            </button>
                            <button id="btn-progress" onclick="switchFisikContent('progress')"
                                class="btn btn-xs btn-light btn-menu-fisik fw-bold shadow-sm"
                                style="font-size: 9px; border-width: 1.5px;">

                                <i data-lucide="activity" class="me-1" style="width: 12px; height: 12px;"></i>

                                PROGRESS
                            </button>
                        </div>
                        <button onclick="refreshCurrentFisikPage()"
                            class="btn btn-xs btn-outline-light fw-bold shadow-sm d-flex align-items-center"
                            style="font-size: 10px; border-width: 1.5px;">
                            <i data-lucide="refresh-cw" class="me-1" style="width: 14px; height: 14px;"></i> REFRESH
                        </button>
                        <a href="{{ route('dashboard.index') }}" onclick="localStorage.clear();"
                            class="btn btn-xs btn-outline-light fw-bold shadow-sm d-flex align-items-center"
                            style="font-size: 10px; border-width: 1.5px;">
                            <i data-lucide="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> MENU UTAMA
                        </a>
                    </div>
                </div>
                <div class="card-body p-3 d-flex flex-column h-100" style="overflow: hidden; background-color: #f8f9fa;">
                    <div class="row g-3 h-100" id="fisik-dynamic-content">
                        @include('dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard')
                    </div>
                </div>
            </div>
        </div>

        {{-- 2.3. SECTION: TRIPLE MATCH --}}
        <div id="section-triple-match" class="content-section" style="height: calc(100vh - 50px);">
            <div class="flex-grow-1 d-flex align-items-center justify-content-center bg-white shadow-sm border rounded-4"
                style="border-color: #09080d !important; border-width: 2px;">
                <div class="text-center p-5">
                    <i class="fa-solid fa-shield-halved mb-3" style="font-size: 60px; color: #09080d;"></i>
                    <h4 class="fw-bold" style="color: #09080d;">Triple Match Integrity System</h4>
                    <p class="text-muted small mb-3">Sinkronisasi tiga arah: Oracle System, Barcode Scan, & Aktual Fisik.
                    </p>
                    <a href="{{ route('dashboard.index') }}" onclick="localStorage.clear();"
                        class="btn btn-sm btn-dark fw-bold rounded-pill px-4">Kembali Ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal 2.1. SECTION: ORACLE VS BARCODE --}}
    <div class="modal fade" id="modalDetailPattern" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h6 class="modal-title small fw-bold mb-0">PATTERN: <span id="modalTitlePattern"
                                class="text-warning"></span></h6>
                        <div id="sku-stats" class="d-flex align-items-center gap-2"></div>
                    </div><button type="button" class="btn-close btn-close-white ms-3" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 bg-light overflow-hidden d-flex flex-column">
                    <div id="modalDetailContent" class="h-100"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalDeepDetail" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title small fw-bold mb-0">Location Detail: <span id="deepDetailTitle"></span></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="bg-light px-3 py-2 border-bottom d-flex justify-content-between align-items-center"><span
                            class="small text-muted fw-bold italic" style="font-size: 10px;"><i
                                class="fa-solid fa-circle-info me-1"></i> *Data Monitoring Stock</span><button
                            id="btnExportDeepExcel" class="btn btn-success btn-xs fw-bold px-3 shadow-sm"><i
                                class="fa-solid fa-file-excel me-1"></i> EXPORT</button></div>
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

    <script>
        let activeFisikMenu = 'default';
        window.oracleDates = @json($oracleDates ?? []);
        window.barcodeDates = @json($barcodeDates ?? []);
        window.activeDates = @json($activeDates ?? []);

        function switchDashboard(sectionId, btnElement, activeClass) {
            document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
            const targetSection = document.getElementById(sectionId);
            if (targetSection) targetSection.classList.add('active');

            // 🎯 MEMORY CONTROL: Amankan status pilar induk agar tetep inget pas di-refresh (F5)
            localStorage.setItem('app_active_section', sectionId);
            localStorage.setItem('app_active_class', activeClass);

            if (sectionId === 'section-oracle-fisik') {
                let savedSubMenu = localStorage.getItem('app_active_sub_menu') || 'default';
                switchFisikContent(savedSubMenu);
            }

            if (sectionId === 'section-oracle-barcode' && typeof myChart !== 'undefined') {
                setTimeout(() => {
                    // Validasi objeknya dulu sebelum manggil method-nya
                    if (myChart) {
                        myChart.resize();
                    }
                }, 300);
            }
        }

        function switchFisikContent(menu) {
            activeFisikMenu = menu;
            const container = document.getElementById('fisik-dynamic-content');
            if (!container) return;

            // 🎯 MEMORY CONTROL SUB-MENU: Amankan nama sub-menu aktif proyek lu bro!
            localStorage.setItem('app_active_sub_menu', menu);

            const allButtons = document.querySelectorAll('.btn-menu-fisik');
            allButtons.forEach(btn => {
                btn.classList.remove('btn-light');
                btn.classList.add('btn-outline-light');
            });

            const activeBtn = document.getElementById('btn-' + menu);
            if (activeBtn) {
                activeBtn.classList.remove('btn-outline-light');
                activeBtn.classList.add('btn-light');
            }

            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-orange" role="status"></div>
                    <p class="mt-2 fw-bold text-muted">Sedang Memuat ${menu.replace('_', ' ').toUpperCase()}...</p>
                </div>
            `;

            fetch("{{ route('oracle_fisik.switch') }}?menu=" + menu, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Gagal mengambil data.');
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                    // Tambahkan kondisi ini untuk Dashboard Fisik Utama
                    if (menu === 'default') {
                        $.getScript("{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_dashboard.js') }}");
                    }

                    // ENGINE AUTOMATION INJECTOR SCRIPT SUB-MENU
                    if (menu === 'master_size') {
                        $.getScript("{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_master_size.js') }}").done(
                            function() {
                                if (typeof window.initMasterSizeMenu === 'function') window.initMasterSizeMenu();
                            });
                    } else if (menu === 'barcode_monitoring') {
                        $.getScript("{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_barcode_monstock.js') }}")
                            .done(function() {
                                if (typeof window.initBarcodeMonstockMenu === 'function') window
                                    .initBarcodeMonstockMenu();
                            });
                    } else if (menu === 'pic') {
                        $.getScript("{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_pic.js') }}").done(
                            function() {
                                if (typeof window.initMasterPicMenu === 'function') window.initMasterPicMenu();
                            });
                    } else if (menu === 'tagstock') {
                        $.getScript("{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_tagstock.js') }}").done(
                            function() {
                                if (typeof window.initTagStockMenu === 'function') window.initTagStockMenu();
                            });
                    } else if (menu === 'appkso') {
                        let excelLibUrl = "{{ asset('js/excel.min.js') }}";
                        let appksoJsUrl = "{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_appkso.js') }}";
                        $.getScript(excelLibUrl).done(function() {
                            $.getScript(appksoJsUrl);
                        });
                    } else if (menu === 'oracle_snapshot') {
                        let excelLibUrl = "{{ asset('js/excel.min.js') }}";
                        let snapshotJsUrl = "{{ asset('js/dashboard/oracle_vs_fisik/oracle_vs_fisik_snapshot.js') }}";
                        $.getScript(excelLibUrl).done(function() {
                            $.getScript(snapshotJsUrl);
                        });
                    }

                    let currentToken = $("input[name='_token']").val() || $('meta[name="csrf-token"]').attr("content");
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': currentToken
                        }
                    });
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML =
                        `<div class="col-12 text-center py-5"><div class="alert alert-danger d-inline-block">Gagal memuat menu: ${menu}.</div></div>`;
                });
        }

        function refreshCurrentFisikPage() {
            switchFisikContent(activeFisikMenu);
        }

        // 🎯 🚀 RESTORE LOGIC MEMORY BROWSER KETIKA USER TOMBOL REFRESH (F5) 🚀 🎯
        document.addEventListener('DOMContentLoaded', function() {
            let initialToken = $("input[name='_token']").val() || $('meta[name="csrf-token"]').attr('content');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': initialToken
                }
            });

            let savedSection = localStorage.getItem('app_active_section');
            let savedClass = localStorage.getItem('app_active_class');

            if (savedSection && savedSection !== 'section-default') {
                console.log("UX Memory Engine: Mengembalikan layar kerja lu secara otomatis bro...");

                // Matikan view pilar opening screen default bawaan web
                document.getElementById('section-default').classList.remove('active');

                // Langsung loncat memicu rendering section active lu bro
                switchDashboard(savedSection, null, savedClass);
            }
        });
    </script>
    <script src="{{ asset('js/dashboard/oracle_vs_barcode.js') }}"></script>
@endsection
