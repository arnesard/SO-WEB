<div class="row g-0">
    <!-- CSS INTERNAL KHUSUS EFEK HOVER GLASS & ANIMASI REFRESH -->
    <style>
        /* 1. Efek Dasar */
        .glass-card {
            transition: all 0.3s ease;
        }

        /* 2. Logic hover (Naik & Bayangan) */
        .glass-card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
        }

        /* 3. Warna Hover Dinamis */
        .hover-orange:hover {
            background-color: #fe6807 !important;
            border-color: #fe6807 !important;
        }

        .hover-cyan:hover {
            background-color: #06b6d4 !important;
            border-color: #06b6d4 !important;
        }

        .hover-purple:hover {
            background-color: #8b5cf6 !important;
            border-color: #8b5cf6 !important;
        }

        .hover-emerald:hover {
            background-color: #10b981 !important;
            border-color: #10b981 !important;
        }

        .hover-rose:hover {
            background-color: #f43f5e !important;
            border-color: #f43f5e !important;
        }

        .hover-amber:hover {
            background-color: #f59e0b !important;
            border-color: #f59e0b !important;
        }

        /* 4. Ubah isi dalam jadi putih saat hover, KECUALI pembungkus ikon agar warna ikon tetap kontras */
        .glass-card-hover:hover *:not(i):not(svg):not(path):not(circle):not(line):not(polyline):not(rect) {
            color: #ffffff !important;
            fill: #ffffff !important;
        }

        .glass-card-hover:hover .icon-wrapper,
        .glass-card-hover:hover .icon-wrapper svg {
            color: #ffffff !important;
            stroke: #ffffff !important;
        }

        .glass-card-hover:hover .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }

        /* 5. Kontainer */
        .metric-cards-container {
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .metric-cards-container.show {
            opacity: 1;
            pointer-events: auto;
        }

        /* Tambahan buat modal */
        .bg-orange {
            background-color: #fe6807 !important;
            color: #ffffff !important;
        }

        .bg-info {
            background-color: #06b6d4 !important;
            color: #ffffff !important;
        }

        /* Memastikan teks dalam tabel modal tidak terlalu kecil */
        #modalUnscannedSKU table {
            font-size: 11px !important;
        }
    </style>

    <!-- HEADER DASHBOARD (GLASS THEME WITH CUSTOM ORANGE ACCENT) -->
    <div class="col-12 mt-1 mb-1">
        <div class="card glass-card shadow-sm p-2 rounded-4 d-flex flex-row align-items-center justify-content-between"
            style="border-left: 5px solid #fe6807 !important;">

            <!-- Pembungkus Judul + Icon -->
            <div class="d-flex align-items-center gap-3 ps-2">
                <div class="p-2 rounded-3" style="background: rgba(254, 104, 7, 0.15);">
                    <i data-lucide="boxes" style="width: 24px; height: 24px; color: #fe6807;"></i>
                </div>
                <div>
                    <h5 class="fw-black text-dark mb-0" style="letter-spacing: -0.3px;">Dashboard Stock Opname</h5>
                    <small class="text-muted fw-semibold" style="font-size: 11px;">Oracle vs Aktual Fisik</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 pe-1">
                <select id="filter-dashboard-wh" class="form-select fw-bold border-0 shadow-none"
                    style="width: 230px; background-color: rgba(255, 255, 255, 0.6); backdrop-filter: blur(4px);"
                    onchange="
                                if(this.value) {
                                    // 1. Sembunyikan placeholder pilih gudang
                                    document.getElementById('gudangPlaceholder').classList.add('d-none');

                                    // 2. Tampilkan container metric cards (efek opacity)
                                    document.getElementById('metricCardsArea').classList.add('show');

                                    // 3. Tampilkan area chart & table (buang d-none terlebih dahulu)
                                    let chartsArea = document.getElementById('mainChartsArea');
                                    chartsArea.classList.remove('d-none');

                                    // 4. Panggil fungsi penarik data / render chart
                                    window.loadFisikDashboardData(this.value);

                                    // 5. Munculkan tombol refresh
                                    document.getElementById('btn-refresh-dashboard').classList.remove('d-none');
                                } else {
                                    // Jika user memilih kembali ke kosongan
                                    document.getElementById('gudangPlaceholder').classList.remove('d-none');
                                    document.getElementById('metricCardsArea').classList.remove('show');
                                    document.getElementById('mainChartsArea').classList.add('d-none');
                                    document.getElementById('btn-refresh-dashboard').classList.add('d-none');
                                }
                            ">
                    <option value="">-- 🏭 PILIH GUDANG --</option>
                    <option value="APW">APW</option>
                    <option value="BPW">BPW</option>
                    <option value="DPW">DPW</option>
                    <option value="RPW">RPW</option>
                </select>

                <button id="btn-refresh-dashboard"
                    onclick="let wh = document.getElementById('filter-dashboard-wh').value; if(wh) window.loadFisikDashboardData(wh);"
                    class="btn btn-refresh-active border-0 shadow-none p-2 d-flex align-items-center justify-content-center d-none rounded-3"
                    style="background: rgba(254, 104, 7, 0.15); color: #fe6807;">
                    <i data-lucide="refresh-cw" style="width: 18px; height: 18px; transition: transform 0.3s;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- METRIC CARDS ROW -->
    <div id="metricCardsArea" class="col-12 mb-1 metric-cards-container">
        <div class="row g-3">

            <!-- CARD 1: Variance Grade OE -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalGrade('OE')"
                    class="card glass-card glass-card-hover hover-orange border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column justify-content-center w-100">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 10px; background-color: rgba(254, 104, 7, 0.15); color: #fe6807; letter-spacing: 0.5px;">
                                    Variance Grade OE
                                </span>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <h3 id="oe-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                    style="font-size: 1.875rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 13px;">Pcs</span>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-danger" style="font-size: 12px;">
                                    <i data-lucide="trending-down" class="me-0.5"
                                        style="width: 14px; height: 14px;"></i>
                                    <span id="oe-sku-minus" class="fw-bold ms-1">0</span><span
                                        class="ms-0.5 text-muted"> SKU</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px;">|</div>
                                <div class="d-flex align-items-center text-success" style="font-size: 12px;">
                                    <i data-lucide="trending-up" class="me-0.5" style="width: 14px; height: 14px;"></i>
                                    <span id="oe-sku-plus" class="fw-bold ms-1">0</span><span class="ms-0.5 text-muted">
                                        SKU</span>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Kanan -->
                        <div class="icon-wrapper text-opacity-75 flex-shrink-0 ms-2" style="color: #fe6807;">
                            <i data-lucide="git-compare" style="width: 28px; height: 28px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Variance Grade OK -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalGrade('OK')"
                    class="card glass-card glass-card-hover hover-cyan border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column justify-content-center w-100">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 9px; background-color: rgba(6, 182, 212, 0.15); color: #06b6d4; letter-spacing: 0.5px;">
                                    Variance Grade OK
                                </span>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <h3 id="ok-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                    style="font-size: 1.875rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 13px;">Pcs</span>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-danger" style="font-size: 12px;">
                                    <i data-lucide="trending-down" class="me-0.5"
                                        style="width: 14px; height: 14px;"></i>
                                    <span id="ok-sku-minus" class="fw-bold ms-1">0</span><span
                                        class="ms-0.5 text-muted"> SKU</span>
                                </div>
                                <div class="text-muted" style="font-size: 10px;">|</div>
                                <div class="d-flex align-items-center text-success" style="font-size: 12px;">
                                    <i data-lucide="trending-up" class="me-0.5"
                                        style="width: 14px; height: 14px;"></i>
                                    <span id="ok-sku-plus" class="fw-bold ms-1">0</span><span
                                        class="ms-0.5 text-muted"> SKU</span>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Kanan -->
                        <div class="icon-wrapper text-opacity-75 flex-shrink-0 ms-2" style="color: #06b6d4;">
                            <i data-lucide="git-compare" style="width: 28px; height: 28px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: Variance Gabungan (Grade OE + Grade OK) -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalGrade('MIX')"
                    class="card glass-card glass-card-hover hover-purple border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column justify-content-center w-100">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 9px; background-color: rgba(139, 92, 246, 0.15); color: #8b5cf6; letter-spacing: 0.5px;">
                                    Variance OE + OK
                                </span>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <h3 id="mix-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                    style="font-size: 1.875rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 13px;">Pcs</span>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-danger" style="font-size: 12px;">
                                    <i data-lucide="trending-down" class="me-0.5"
                                        style="width: 14px; height: 14px;"></i>
                                    <span id="mix-sku-minus" class="fw-bold ms-1">0</span><span
                                        class="ms-0.5 text-muted"> SKU</span>
                                </div>
                                <div class="text-muted" style="font-size: 10px;">|</div>
                                <div class="d-flex align-items-center text-success" style="font-size: 12px;">
                                    <i data-lucide="trending-up" class="me-0.5"
                                        style="width: 14px; height: 14px;"></i>
                                    <span id="mix-sku-plus" class="fw-bold ms-1">0</span><span
                                        class="ms-0.5 text-muted"> SKU</span>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Kanan -->
                        <div class="icon-wrapper text-opacity-75 flex-shrink-0 ms-2" style="color: #8b5cf6;">
                            <i data-lucide="layers" style="width: 28px; height: 28px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 4: Variance Performance (PPM) -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalPPM()"
                    class="card glass-card glass-card-hover hover-emerald border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between h-100">
                        <div class="d-flex flex-column justify-content-center">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 9px; background-color: rgba(249, 115, 22, 0.15) !important; color: #f97316; letter-spacing: 0.5px;">
                                    Variance Rate (PPM)
                                </span>
                            </div>
                            <div class="d-flex align-items-baseline">
                                <h3 id="sum-ppm" class="text-dark mb-0 fw-black tracking-tight"
                                    style="font-size: 1.875rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 13px;">PPM</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-danger" style="font-size: 11px;">
                                    <i data-lucide="target" class="me-0.5" style="width: 14px; height: 14px;"></i>
                                    <span class="ms-0.5 text-muted">Target : 35 PPM</span>
                                </div>
                            </div>
                        </div>
                        <div class="icon-wrapper text-warning text-opacity-75 ms-2">
                            <i data-lucide="gauge" style="width: 28px; height: 28px; color: #f97316;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 5: Price Variance -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalPriceVariance()"
                    class="card glass-card glass-card-hover hover-rose border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between h-100">

                        <div class="d-flex flex-column justify-content-center flex-grow-1 overflow-hidden pe-2">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 9px; background-color: rgba(244, 63, 94, 0.15) !important; color: #f43f5e; letter-spacing: 0.5px;">
                                    Price Variance
                                </span>
                            </div>

                            <div class="d-flex align-items-baseline" style="min-height: 28px;">
                                <h3 id="sum-price-variance"
                                    class="text-dark mb-0 fw-black tracking-tight text-truncate"
                                    style="font-size: 1.5rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 13px; visibility: hidden;">Rp</span>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-dark text-truncate"
                                    style="font-size: 10px;">
                                    <span class="fw-bold">OE: </span><span id="price-variance-oe"
                                        class="ms-1">0</span>
                                </div>
                                <div class="text-muted" style="font-size: 10px;">|</div>
                                <div class="d-flex align-items-center text-dark text-truncate"
                                    style="font-size: 10px;">
                                    <span class="fw-bold">OK: </span><span id="price-variance-ok"
                                        class="ms-1">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="icon-wrapper text-danger text-opacity-75 ms-1 flex-shrink-0">
                            <i data-lucide="dollar-sign" style="width: 28px; height: 28px; color: #f43f5e;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 6: Persentase SKU -->
            <div class="col-6 col-md-2">
                <div onclick="window.openModalUnscanned()"
                    class="card glass-card glass-card-hover hover-amber border-start border-4 rounded-4 py-2 px-3 h-100"
                    style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex flex-column justify-content-center w-100">
                            <div class="mb-1">
                                <span class="badge rounded-pill fw-bold text-uppercase px-2 py-0.5"
                                    style="font-size: 9px; background-color: rgba(245, 158, 11, 0.15) !important; color: #f59e0b; letter-spacing: 0.5px;">
                                    Persentase SKU
                                </span>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <h3 id="sku-percentage" class="text-dark mb-0 fw-black tracking-tight"
                                    style="font-size: 1.875rem;">0</h3>
                                <span class="text-muted ms-1" style="font-size: 12px; font-weight: bold;">%</span>
                            </div>

                            <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top"
                                style="border-color: rgba(0,0,0,0.1) !important;">
                                <div class="d-flex align-items-center text-primary" style="font-size: 11px;">
                                    <span id="sku-counted" class="fw-bold">0</span><span class="ms-0.5 text-muted">
                                        Counted</span>
                                </div>
                                <div class="text-muted" style="font-size: 10px;">/</div>
                                <div class="d-flex align-items-center text-dark" style="font-size: 11px;">
                                    <span id="sku-onhand" class="fw-bold">0</span><span class="ms-0.5 text-muted">
                                        On-hand</span>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Kanan -->
                        <div class="icon-wrapper text-opacity-75 flex-shrink-0 ms-2" style="color: #f59e0b;">
                            <i data-lucide="percent" style="width: 28px; height: 28px;"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- MAIN CONTAINER -->
    <div class="col-12" style="height: 55vh; overflow-y: auto; overflow-x: hidden; padding-right: 4px;">

        <!-- 1. PLACEHOLDER: MUNCUL HANYA SAAT GUDANG BELUM DIPILIH ATAU DATA KOSONG -->
        <div id="gudangPlaceholder" class="d-flex flex-column align-items-center justify-content-center w-100"
            style="height: 50vh;">
            <div class="alert alert-warning d-flex align-items-center justify-content-center py-2 px-4 border-0 rounded-3 shadow-sm"
                style="font-size: 14px; max-width: 400px;">
                <i data-lucide="info" class="me-2" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <div class="fw-medium">Pilih gudang untuk menampilkan data</div>
            </div>
        </div>

        <!-- 2. MAIN CHARTS & TABLE AREA: SEMBUNYIKAN DEFAULT (D-NONE) -->
        <div id="mainChartsArea" class="row g-3 d-none">

            <!-- LEFT COLUMN: SPEEDOMETER & TOTAL QTY -->
            <div class="col-12 col-lg-3">
                <!-- Speedometer Chart Card -->
                <div class="card glass-card rounded-4 p-2 mb-1"
                    style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05) !important;">
                    <div id="speedometerChart" style="height: 180px; width: 100%;"></div>
                </div>

                <!-- Total Qty Chart Card -->
                <div class="card glass-card rounded-4 p-2"
                    style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05) !important;">
                    <div id="totalQtyChart" style="height: 200px; width: 100%;"></div>
                </div>
            </div>

            <!-- RIGHT COLUMN: VARIANCE CHART -->
            <div class="col-12 col-lg-9">
                <!-- Variance Chart Card -->
                <div class="card glass-card rounded-4 p-2 h-100"
                    style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05) !important;">
                    <div id="varianceChart" style="height: 400px; width: 100%;"></div>
                </div>
            </div>

            <!-- BOTTOM ROW: TABLE CONTAINER -->
            <div class="col-12 mb-1 mt-1">
                <div id="tableContainerFisik" class="card glass-card rounded-4 p-3"
                    style="box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05) !important;">
                    <div class="text-center text-muted p-3">Data tabel akan otomatis dimuat...</div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- MODAL 1 --}}
<div class="modal fade" id="modalDetailPattern" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white d-flex align-items-center">
                <h5 class="modal-title fw-bold mb-0" id="modalTitlePattern">PATTERN: </h5>

                <div class="d-flex gap-2 align-items-center ms-auto">
                    <button type="button" class="btn btn-sm btn-success fw-bold border-0"
                        onclick="window.exportModalExcel()">
                        <i data-lucide="file-spreadsheet" class="me-1" style="width: 14px; height: 14px;"></i>
                        Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary fw-bold border-0 me-3"
                        onclick="window.printModal()">
                        <i data-lucide="printer" class="me-1" style="width: 14px; height: 14px;"></i> Print
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light" id="modalDetailContent">
            </div>
        </div>
    </div>
</div>

{{-- MODAL 2 --}}
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
                        <thead class="bg-light sticky-top text-dark">
                            <tr>
                                <th class="text-center" style="width: 5%;">NO</th>
                                <th>OPR ID</th>
                                <th>NAMA OPR</th>
                                <th>NO KSO</th>
                                <th>ITEM CODE</th>
                                <th>DESKRIPSI</th>
                                <th class="text-end text-primary" style="width: 10%;">QTY SCAN</th>
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

{{-- MODAL 3: UNSCANNED SKU --}}
<div class="modal fade" id="modalUnscannedSKU" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h6 class="modal-title fw-bold" id="modalTitleUnscanned">Stock On-hand (Belum Di SCAN)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">

                <div class="mb-3">
                    <input type="text" id="searchUnscanned" class="form-control form-control-sm"
                        placeholder="🔍 Cari Item atau Deskripsi..." onkeyup="filterUnscannedTable()">
                </div>

                <div class="row g-3">
                    <!-- KOLOM GRADE OE -->
                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-orange text-white fw-bold">GRADE OE</span>
                            <span id="count-oe" class="badge bg-secondary rounded-pill">0 Data</span>
                        </div>
                        <div class="table-responsive border rounded" style="max-height: 50vh;">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <!-- Perhatikan bagian ini di kedua tabel (OE & OK) -->
                                <thead class="bg-light sticky-top text-dark">
                                    <tr>
                                        <th class="text-center">NO</th>
                                        <th>ITEM</th>
                                        <th>DESC</th>
                                        <th class="text-end">SISA</th>
                                        <th class="text-center">PROGRES</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-unscanned-oe"></tbody>
                            </table>
                        </div>
                    </div>
                    <!-- KOLOM GRADE OK -->
                    <div class="col-12 col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-info text-white fw-bold">GRADE OK</span>
                            <span id="count-ok" class="badge bg-secondary rounded-pill">0 Data</span>
                        </div>
                        <div class="table-responsive border rounded" style="max-height: 50vh;">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <!-- Perhatikan bagian ini di kedua tabel (OE & OK) -->
                                <thead class="bg-light sticky-top text-dark">
                                    <tr>
                                        <th class="text-center">NO</th>
                                        <th>ITEM</th>
                                        <th>DESC</th>
                                        <th class="text-end">SISA</th>
                                        <th class="text-center">PROGRES</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-unscanned-ok"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 4: REKAP PRICE VARIANCE (Daftar Pattern) --}}
<div class="modal fade" id="modalPriceVariance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-rose text-white" style="background-color: #f43f5e;">
                <h6 class="modal-title fw-bold">Rekap Price Variance (Pattern)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="table-responsive border rounded bg-white" style="max-height: 60vh;">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="sticky-top bg-dark text-white" style="font-size: 11px;">
                            <tr>
                                <th class="ps-3 py-2">Pattern (Grade)</th>
                                <th class="text-end py-2">Counted</th>
                                <th class="text-end py-2">On-hand</th>
                                <th class="text-end py-2">Variance</th>
                                <th class="text-center py-2">SKU (-)</th>
                                <th class="text-center py-2">SKU (+)</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-price-variance-rekap" style="font-size: 12px;">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 5: DETAIL PRICE PATTERN --}}
<div class="modal fade" id="modalDetailPricePattern" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white d-flex align-items-center">
                <h5 class="modal-title fw-bold mb-0" id="modalTitlePriceDetail">PATTERN: </h5>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light">
                <div id="alert-missing-price" class="alert alert-warning d-none align-items-center mb-3 shadow-sm"
                    role="alert">
                    <i data-lucide="alert-triangle" class="me-2 text-danger"></i>
                    <div>
                        <strong>Perhatian:</strong> Terdapat <span id="missing-price-count"
                            class="fw-bold text-danger">0</span> Item pada pattern ini yang master harganya Rp 0 atau
                        belum terdaftar. Total kalkulasi Rupiah mungkin tidak akurat.
                    </div>
                </div>

                <div id="modalPriceDetailContent">
                    <div class="text-center p-5">
                        <div class="spinner-border text-danger"></div>
                        <div class="mt-2 text-muted">Memuat detail harga...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 6: DETAIL PER GRADE --}}
<div class="modal fade" id="modalDetailGrade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white d-flex align-items-center">
                <h5 class="modal-title fw-bold mb-0" id="modalTitleGrade">GRADE: </h5>
                <div class="d-flex gap-2 align-items-center ms-auto">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light" id="modalGradeContent">
            </div>
        </div>
    </div>
</div>

{{-- MODAL 7: DETAIL PPM MATRIX --}}
<div class="modal fade" id="modalDetailPPM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Analisis Variance per Produk (PPM)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 bg-light" id="modalPPMContent">
                <div class="text-center p-5">
                    <div class="spinner-border text-emerald"></div>
                </div>
            </div>
        </div>
    </div>
</div>
