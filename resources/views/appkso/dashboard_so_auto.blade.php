@extends('layouts.app')

@section('content')
    <div class="container-fluid py-2 h-100 d-flex flex-column">
        <div class="row g-0 flex-grow-1">
            <style>
                /* Sembunyikan Navbar bawaan layouts.app */
                nav,
                .navbar,
                header,
                aside {
                    display: none !important;
                }

                body {
                    margin: 0;
                    min-height: 100vh;
                    /* Gradasi radial gelap dari tengah ke pojok */
                    background: radial-gradient(circle at 50% 50%, #1e293b, #0f172a, #020617);
                    color: #e2e8f0;
                    font-family: 'Segoe UI', sans-serif;
                }

                .glass-card {
                    transition: all 0.3s ease;
                }

                .glass-card-hover:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
                }

                .hover-orange:hover {
                    background-color: #68a00e !important;
                    border-color: #68a00e !important;
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

                /* Update CSS ini: Tambahkan pengecualian :not(table *) supaya tabel tetap hitam saat hover */
                .glass-card-hover:hover *:not(i):not(svg):not(path):not(circle):not(line):not(polyline):not(rect):not(table *):not(th):not(td) {
                    color: #ffffff !important;
                    fill: #ffffff !important;
                    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
                }

                /* Pastikan tabel tetap hitam meski card di-hover */
                .glass-card-hover:hover table,
                .glass-card-hover:hover table th,
                .glass-card-hover:hover table td {
                    color: #000 !important;
                }

                /* Tambahkan Border pada Tabel */
                .table-bordered-custom {
                    border: 1px solid #dee2e6 !important;
                }

                .table-bordered-custom th,
                .table-bordered-custom td {
                    border: 1px solid #dee2e6 !important;
                }



                .glass-card-hover:hover .icon-wrapper,
                .glass-card-hover:hover .icon-wrapper svg {
                    color: #ffffff !important;
                    stroke: #ffffff !important;
                }

                .glass-card-hover:hover .badge {
                    background-color: rgba(255, 255, 255, 0.3) !important;
                    color: #ffffff !important;
                }

                .metric-cards-container {
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    pointer-events: none;
                }

                .metric-cards-container.show {
                    opacity: 1;
                    pointer-events: auto;
                }

                .bg-orange {
                    background-color: #fe6807 !important;
                    color: #ffffff !important;
                }

                .bg-info {
                    background-color: #06b6d4 !important;
                    color: #ffffff !important;
                }

                /* Modal Table Size */
                .modal-body table th {
                    padding: 10px;
                }

                .modal-body table td {
                    vertical-align: middle;
                }

                /* Efek Hover Card */
                .hover-lift-card {
                    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                    background-color: #ffffff;
                }

                .hover-lift-card:hover {
                    transform: translateY(-8px);
                    /* Card naik 8px */
                    box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1) !important;
                    /* Shadow lebih tebal */
                    background-color: #f8fafc;
                    /* Sedikit lebih gelap/abu-abu saat di-hover */
                }

                /* Efek Hover dengan Warna Spesifik */
                .hover-lift-card:hover.border-blue {
                    background-color: #dbebff !important;
                }

                .hover-lift-card:hover.border-green {
                    background-color: #d1ffdf !important;
                }

                .hover-lift-card:hover.border-orange {
                    background-color: #fff4c7 !important;
                }

                .hover-row:hover {
                    background-color: #f1f5f9 !important;
                }
            </style>

            <div class="col-12 mt-0 mb-2">
                <div
                    class="card glass-card shadow-sm p-2 rounded-4 d-flex flex-row align-items-center justify-content-between">

                    <div class="d-flex align-items-center gap-3 ps-2">
                        <img src="{{ asset('images/logo-gt.png') }}" alt="Logo" style="height: 45px;">
                        <div class="d-flex flex-column lh-1">
                            <span class="fw-bold text-dark" style="font-size: 1.2rem;">PT. Gajah Tunggal Tbk.,</span>
                            <span class="text-muted" style="font-size: 1rem;">Gudang Ban B Dept.</span>
                        </div>
                        {{-- <div class="p-2 rounded-3" style="background: rgba(254, 104, 7, 0.15);">
                            <i data-lucide="boxes" style="width: 24px; height: 24px; color: #fe6807;"></i>
                        </div>
                        <div>
                            <h5 class="fw-black text-dark mb-0" style="letter-spacing: -0.3px;">Dashboard Stock Opname
                                Gudang Ban B</h5>
                            <small class="text-muted fw-semibold" style="font-size: 11px;">*Klik Card Untuk melihat detail
                                Data</small>
                        </div> --}}
                    </div>

                    <div class="d-flex align-items-center gap-3 ps-2">

                        <div>
                            <span class="fw-bold text-dark" style="font-size: 1.8rem;">Dashboard Stock Opname</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pe-1">
                        <button id="btn-refresh-dashboard" onclick="window.toggleAutoRefresh(this);"
                            class="btn btn-refresh-active border-0 shadow-sm p-2 d-flex align-items-center justify-content-center d-none rounded-3"
                            style="background: #fe6807; color: #fff; transition: 0.3s; min-width: 40px;"
                            data-bs-toggle="tooltip" data-bs-placement="bottom"
                            title="Klik untuk aktifkan Refresh Otomatis (10 detik)">
                            <i data-lucide="refresh-cw" style="width: 18px; height: 18px;"></i>
                        </button>
                        <select id="filter-dashboard-so" class="form-select fw-bold border-0 shadow-sm"
                            style="width: 350px; background-color: #fff;"
                            onchange="
                            if(this.value) {
                                document.getElementById('gudangPlaceholder').classList.add('d-none');
                                document.getElementById('metricCardsArea').classList.add('show');
                                document.getElementById('mainChartsArea').classList.remove('d-none');
                                window.loadFisikDashboardData(this.value);
                                document.getElementById('btn-refresh-dashboard').classList.remove('d-none');
                            } else {
                                document.getElementById('gudangPlaceholder').classList.remove('d-none');
                                document.getElementById('metricCardsArea').classList.remove('show');
                                document.getElementById('mainChartsArea').classList.add('d-none');
                                document.getElementById('btn-refresh-dashboard').classList.add('d-none');

                                // TAMBAHAN: Sembunyikan summary tabel saat opsi dikosongkan
                                document.getElementById('summaryTablesArea').classList.add('d-none');
                            }
                        ">
                            <option value="">-- 📋 PILIH SO ACTIVE --</option>
                            @if (isset($list_kso))
                                @foreach ($list_kso as $kso)
                                    <option value="{{ $kso->so_name }}">{{ $kso->so_name }}</option>
                                @endforeach
                            @endif
                        </select>


                        <a href="{{ route('appkso.index') }}" class="btn border-0 shadow-sm p-2 ms-1 rounded-3 text-white"
                            style="background-color: #334155;">
                            <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <div id="metricCardsArea" class="col-12 mb-2 metric-cards-container">
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalGrade('OE')"
                            class="card glass-card card-oe glass-card-hover hover-orange rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1"><span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 18px; background-color: rgba(7, 254, 28, 0.15); color: #68a00e;">Variance
                                            Grade OE*</span></div>
                                    <div class="d-flex align-items-baseline">
                                        <h3 id="oe-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">0</h3><span class="text-muted ms-1"
                                            style="font-size: 13px;">Pcs</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-danger" style="font-size: 12px;"><i
                                                data-lucide="trending-down" class="me-1" style="width: 14px;"></i><span
                                                id="oe-sku-minus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                        <div class="text-muted">|</div>
                                        <div class="d-flex align-items-center text-success" style="font-size: 12px;"><i
                                                data-lucide="trending-up" class="me-1" style="width: 14px;"></i><span
                                                id="oe-sku-plus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                    </div>
                                </div>
                                <div class="icon-wrapper ms-2" style="color: #fe6807;"><i data-lucide="git-compare"
                                        style="width: 28px; height: 28px;"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalGrade('OK')"
                            class="card glass-card card-ok glass-card-hover hover-cyan rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1"><span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 18px; background-color: rgba(6, 182, 212, 0.15); color: #06b6d4;">Variance
                                            Grade OK*</span></div>
                                    <div class="d-flex align-items-baseline">
                                        <h3 id="ok-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">0</h3><span class="text-muted ms-1"
                                            style="font-size: 13px;">Pcs</span>.
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-danger" style="font-size: 12px;"><i
                                                data-lucide="trending-down" class="me-1" style="width: 14px;"></i><span
                                                id="ok-sku-minus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                        <div class="text-muted">|</div>
                                        <div class="d-flex align-items-center text-success" style="font-size: 12px;"><i
                                                data-lucide="trending-up" class="me-1" style="width: 14px;"></i><span
                                                id="ok-sku-plus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                    </div>
                                </div>
                                <div class="icon-wrapper ms-2" style="color: #06b6d4;"><i data-lucide="git-compare"
                                        style="width: 28px; height: 28px;"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalGrade('MIX')"
                            class="card glass-card card-mix glass-card-hover hover-purple rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1"><span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 18px; background-color: rgba(139, 92, 246, 0.15); color: #8b5cf6;">Variance
                                            OE + OK*</span></div>
                                    <div class="d-flex align-items-baseline">
                                        <h3 id="mix-variance-pcs" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">0</h3><span class="text-muted ms-1"
                                            style="font-size: 13px;">Pcs</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-danger" style="font-size: 12px;"><i
                                                data-lucide="trending-down" class="me-1" style="width: 14px;"></i><span
                                                id="mix-sku-minus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                        <div class="text-muted">|</div>
                                        <div class="d-flex align-items-center text-success" style="font-size: 12px;"><i
                                                data-lucide="trending-up" class="me-1" style="width: 14px;"></i><span
                                                id="mix-sku-plus" class="fw-bold">0</span><span
                                                class="ms-1 text-muted">SKU</span></div>
                                    </div>
                                </div>
                                <div class="icon-wrapper ms-2" style="color: #8b5cf6;"><i data-lucide="layers"
                                        style="width: 28px; height: 28px;"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalPPM()"
                            class="card glass-card card-ppm glass-card-hover hover-emerald rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1"><span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 17px; background-color: rgba(16, 185, 129, 0.15); color: #10b981;">Variance
                                            Rate (PPM)*</span></div>
                                    <div class="d-flex align-items-baseline">
                                        <h3 id="sum-ppm" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">0</h3><span class="text-muted ms-1 fw-bold"
                                            style="font-size: 14px;">PPM</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-danger" style="font-size: 12px;"><i
                                                data-lucide="target" class="me-1" style="width: 14px;"></i><span
                                                class="text-muted fw-bold">Target : 35 PPM</span></div>
                                    </div>
                                </div>
                                <div class="icon-wrapper ms-2" style="color: #10b981;"><i data-lucide="gauge"
                                        style="width: 28px; height: 28px;"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalPriceVariance()"
                            class="card glass-card glass-card-hover hover-rose rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">

                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1">
                                        <span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 18px; background-color: rgba(244, 63, 94, 0.15); color: #f43f5e;">
                                            Price Variance*
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-baseline">
                                        <h3 id="sum-price-variance" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">Rp 0</h3>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-dark" style="font-size: 12px;">
                                            <span class="fw-bold">OE: </span>
                                            <span id="price-variance-oe" class="ms-1">0</span>
                                        </div>
                                        <div class="text-muted">|</div>
                                        <div class="d-flex align-items-center text-dark" style="font-size: 12px;">
                                            <span class="fw-bold">OK: </span>
                                            <span id="price-variance-ok" class="ms-1">0</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="icon-wrapper ms-2" style="color: #f43f5e;">
                                    <i data-lucide="dollar-sign" style="width: 28px; height: 28px;"></i>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-2">
                        <div onclick="window.openModalUnscanned()"
                            class="card glass-card card-sku glass-card-hover hover-amber rounded-4 py-2 px-3 h-100"
                            style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex flex-column justify-content-center w-100">
                                    <div class="mb-1"><span class="badge rounded-pill fw-bold px-2 py-1"
                                            style="font-size: 18px; background-color: rgba(245, 158, 11, 0.15); color: #f59e0b;">Persentase
                                            SKU*</span></div>
                                    <div class="d-flex align-items-baseline">
                                        <h3 id="sku-percentage" class="text-dark mb-0 fw-black tracking-tight"
                                            style="font-size: 1.875rem;">0</h3><span class="text-muted ms-1 fw-bold"
                                            style="font-size: 14px;">%</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                                        <div class="d-flex align-items-center text-primary" style="font-size: 12px;"><span
                                                id="sku-counted" class="fw-bold">0</span><span class="ms-1 text-muted">
                                                Counted</span></div>
                                        <div class="text-muted">|</div>
                                        <div class="d-flex align-items-center text-dark" style="font-size: 12px;"><span
                                                id="sku-onhand" class="fw-bold">0</span><span class="ms-1 text-muted">
                                                On-hand</span></div>
                                    </div>
                                </div>
                                <div class="icon-wrapper ms-2" style="color: #f59e0b;"><i data-lucide="percent"
                                        style="width: 28px; height: 28px;"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                <div id="gudangPlaceholder"
                    class="d-flex flex-column align-items-center justify-content-center w-100 h-100">
                    <div class="alert alert-warning d-flex align-items-center py-2 px-4 shadow-sm border-0 rounded-3">
                        <i data-lucide="info" class="me-2 text-warning"></i>
                        <div class="fw-bold">Pilih SO Active untuk menampilkan data visualisasi.</div>
                    </div>
                    <div class="alert alert-warning d-flex align-items-center py-2 px-4 shadow-sm border-0 rounded-3">
                        <div class="fw-bold">*Klik Card / Grafik Batang / Baris Tabel untuk melihat detail data.</div>
                    </div>
                </div>

                <div id="mainChartsArea" class="row g-2 d-none flex-grow-1 pb-0">
                    <div class="col-12 col-lg-1">
                        <div class="card glass-card rounded-4 p-3 shadow-sm d-flex flex-column hover-lift-card border-blue"
                            style="border-top: 4px solid #3b82f6; height: 280px;">
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 13px;">
                                    <i data-lucide="activity" class="me-2"
                                        style="width: 16px; height: 16px; color: #3b82f6;"></i>
                                    Progress
                                </h6>
                            </div>
                            <div id="speedometerChart" class="flex-grow-1" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2">
                        <div class="card glass-card rounded-4 p-3 shadow-sm d-flex flex-column hover-lift-card border-green"
                            style="border-top: 4px solid #65a30d; height: 280px;">
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 13px;">
                                    <i data-lucide="bar-chart-2" class="me-2"
                                        style="width: 16px; height: 16px; color: #65a30d;"></i>
                                    Stock Performance
                                </h6>
                            </div>
                            <div id="totalQtyChart" class="flex-grow-1" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2">
                        <div class="card glass-card rounded-4 p-3 shadow-sm d-flex flex-column hover-lift-card border-info"
                            style="border-top: 4px solid #0bdaf5; height: 280px;">
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 13px;">
                                    <i data-lucide="clipboard-list" class="me-2"
                                        style="width: 16px; height: 16px; color: #0bdaf5;"></i>
                                    Resume Data
                                </h6>
                            </div>

                            <div id="resumeChart" class="flex-grow-1" style="width: 100%;"></div>

                        </div>
                    </div>

                    <div class="col-12 col-lg-1">
                        <div class="card glass-card rounded-4 p-3 shadow-sm d-flex flex-column hover-lift-card border-info"
                            style="border-top: 4px solid #0bdaf5; height: 280px;">
                            <div class="d-flex align-items-center mb-1">
                                <!-- <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 13px;">
                                    <i data-lucide="layers" class="me-2" style="width: 16px; height: 16px; color: #0bdaf5;"></i>
                                    Resume TIRE & TUBE
                                </h6> -->
                            </div>

                            <div id="tireTubeChart" class="flex-grow-1" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card glass-card rounded-4 p-3 shadow-sm d-flex flex-column hover-lift-card border-orange"
                            style="border-top: 4px solid #f59e0b; height: 280px;">
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-size: 13px;">
                                    <i data-lucide="list-ordered" class="me-2"
                                        style="width: 16px; height: 16px; color: #f59e0b;"></i>
                                    Top 10 Pattern Variance*
                                </h6>
                            </div>
                            <div id="varianceChart" class="flex-grow-1" style="width: 100%;"></div>
                        </div>
                    </div>

                </div>
                <div id="summaryTablesArea" class="row g-2 mt-0 d-none">

                    <div class="col-12 col-md-6">
                        <div class="card glass-card rounded-4 p-2 shadow-sm h-100 glass-card-hover hover-orange"
                            style="border-start: 4px solid #fe6807;">
                            <div class="mb-2 border-bottom pb-2">
                                <h6 class="fw-bold mb-1 text-dark">Pattern (OE)*</h6>
                                <div id="summary-header-oe" class="d-flex flex-wrap gap-2 fw-semibold"
                                    style="font-size: 10.5px;"></div>
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0 table-bordered-custom"
                                    id="table-oe" style="font-size: 11px;">
                                    <thead class="sticky-top bg-dark text-white">
                                        <tr>
                                            <th class="ps-2">Pattern</th>
                                            <th class="text-end">Counted</th>
                                            <th class="text-end">On-hand</th>
                                            <th class="text-end">Var</th>
                                            <th class="text-center">SKU (-)</th>
                                            <th class="text-center">SKU (+)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-oe"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card glass-card rounded-4 p-2 shadow-sm h-100 glass-card-hover hover-cyan"
                            style="border-start: 4px solid #06b6d4;">
                            <div class="mb-2 border-bottom pb-2">
                                <h6 class="fw-bold mb-1 text-dark">Pattern (OK)*</h6>
                                <div id="summary-header-ok" class="d-flex flex-wrap gap-2 fw-semibold"
                                    style="font-size: 10.5px;"></div>
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0 table-bordered-custom"
                                    id="table-ok" style="font-size: 11px;">
                                    <thead class="sticky-top bg-dark text-white">
                                        <tr>
                                            <th class="ps-2">Pattern</th>
                                            <th class="text-end">Counted</th>
                                            <th class="text-end">On-hand</th>
                                            <th class="text-end">Var</th>
                                            <th class="text-center">SKU (-)</th>
                                            <th class="text-center">SKU (+)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-ok"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 1: DETAIL PER GRADE --}}
    <div class="modal fade" id="modalDetailGrade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                {{-- MODAL HEADER --}}
                <div class="modal-header bg-dark text-white d-flex align-items-center">
                    <h5 class="modal-title fw-bold mb-0" id="modalTitleGrade">GRADE: </h5>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-info me-2" id="btnToggleSimilar"
                            onclick="toggleAllSimilar()">
                            <i class="fas fa-eye"></i> Tampilkan Semua Similar
                        </button>
                        <button type="button" class="btn btn-sm btn-light fw-bold"
                            onclick="window.printModal('modalTitleGrade', 'modalGradeContent')">
                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>

                {{-- KETERANGAN / PETUNJUK PENGGUNAAN --}}
                <div class="bg-light px-3 pt-3 pb-0 text-muted small">
                    <div class="d-flex flex-wrap gap-3">
                        <span>
                            <strong>*</strong> Klik
                            <span class="badge bg-warning text-dark">S</span>
                            untuk melihat data similar.
                        </span>
                        <span><strong>*</strong> Klik baris untuk melihat detail data Counted.</span>
                    </div>
                </div>

                {{-- MODAL BODY --}}
                <div class="modal-body p-3 bg-light" id="modalGradeContent"></div>

            </div>
        </div>
    </div>

    {{-- MODAL 2: DETAIL PATTERN --}}
    <div class="modal fade" id="modalDetailPattern" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white d-flex align-items-center">
                    <h5 class="modal-title fw-bold mb-0" id="modalTitlePattern">PATTERN: </h5>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-info me-2" id="btnToggleSimilar"
                            onclick="toggleAllSimilar()">
                            <i class="fas fa-eye"></i> Tampilkan Semua Similar
                        </button>
                        <button type="button" class="btn btn-sm btn-light fw-bold"
                            onclick="window.printModal('modalTitlePattern', 'modalDetailContent')">
                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                {{-- KETERANGAN / PETUNJUK PENGGUNAAN --}}
                <div class="bg-light px-3 pt-3 pb-0 text-muted small">
                    <div class="d-flex flex-wrap gap-3">
                        <span>
                            <strong>*</strong> Klik
                            <span class="badge bg-warning text-dark">S</span>
                            untuk melihat data similar.
                        </span>
                        <span><strong>*</strong> Klik baris untuk melihat detail data Counted.</span>
                    </div>
                </div>
                <div class="modal-body p-3 bg-light" id="modalDetailContent"></div>
            </div>
        </div>
    </div>

    {{-- MODAL 3: PRICE VARIANCE REKAP --}}
    <div class="modal fade" id="modalPriceVariance" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white d-flex align-items-center" style="background-color: #f43f5e;">
                    <h5 class="modal-title fw-bold mb-0">Rekap Price Variance (Pattern)</h5>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        {{-- <button type="button" class="btn btn-sm btn-light fw-bold" onclick="window.print()">
                            <i data-lucide="printer" style="width: 14px; height: 14px;"></i> Print
                        </button> --}}
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-3 bg-light">
                    Klik Baris Untuk Melihat Detail
                    <div class="table-responsive border rounded bg-white shadow-sm"
                        style="max-height: 40vh; overflow-y: auto;">
                        <table class="table table-bordered table-hover table-sm align-middle mb-0 text-center"
                            style="font-size: 11px;">
                            <thead class="sticky-top text-white" style="background-color: #1e293b; z-index: 2;">
                                <tr>
                                    <th rowspan="2" class="align-middle">No.</th>
                                    <th rowspan="2" class="align-middle text-start ps-3">Pattern (Grade)</th>
                                    <th colspan="2">Counted</th>
                                    <th colspan="2">On-hand</th>
                                    <th colspan="2">Variance</th>
                                    <th rowspan="2" class="align-middle">SKU (-)</th>
                                    <th rowspan="2" class="align-middle">SKU (+)</th>
                                </tr>
                                <tr>
                                    <th>Pcs</th>
                                    <th>Rp</th>
                                    <th>Pcs</th>
                                    <th>Rp</th>
                                    <th>Pcs</th>
                                    <th>Rp</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-price-variance-rekap" style="font-size: 11.5px;"></tbody>
                        </table>
                    </div>

                    <div id="priceDetailContainer" class="mt-4 d-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 4: DETAIL PPM --}}
    <div class="modal fade" id="modalDetailPPM" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background-color: #10b981; color: white;">
                    <h5 class="modal-title fw-bold">Analisis Variance per Produk (PPM)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3 bg-light" id="modalPPMContent"></div>
            </div>
        </div>
    </div>

    {{-- MODAL 5: UNSCANNED SKU --}}
    <div class="modal fade" id="modalUnscannedSKU" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white d-flex align-items-center" style="background-color: #f59e0b;">
                    <h6 class="modal-title fw-bold mb-0">
                        Stock On-hand vs Counted Progress
                    </h6>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span id="summary-oe-badge" class="badge bg-white fw-bold"
                            style="color:#f59e0b; font-size:11px;"></span>
                        <span id="summary-ok-badge" class="badge bg-white fw-bold"
                            style="color:#06b6d4; font-size:11px;"></span>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body p-3 bg-light">
                    <input type="text" id="searchUnscanned" class="form-control form-control-sm mb-3 shadow-sm"
                        placeholder="🔍 Cari Item atau Deskripsi..." onkeyup="filterUnscannedTable()">

                    <div class="row g-3 h-100">
                        {{-- TABEL OE --}}
                        <div class="col-12 col-md-6 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge fw-bold py-2 px-3" style="background-color:#fe6807;">GRADE OE</span>
                                <span id="count-oe" class="badge bg-secondary">0 Data</span>
                            </div>
                            <div class="table-responsive border rounded bg-white shadow-sm flex-grow-1"
                                style="max-height: calc(99vh - 200px); overflow-y: auto;">
                                <table class="table table-hover table-sm mb-0 align-middle" style="font-size: 11px;">
                                    <thead class="bg-dark text-white sticky-top text-center" style="z-index:2;">
                                        <tr>
                                            <th style="width:4%">NO</th>
                                            <th class="text-start">ITEM</th>
                                            <th class="text-start">DESC</th>
                                            <th>COUNTED</th>
                                            <th>ON-HAND</th>
                                            <th>QTY</th>
                                            <th style="width:15%">PROGRESS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-unscanned-oe"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- TABEL OK --}}
                        <div class="col-12 col-md-6 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge fw-bold py-2 px-3" style="background-color:#06b6d4;">GRADE OK</span>
                                <span id="count-ok" class="badge bg-secondary">0 Data</span>
                            </div>
                            <div class="table-responsive border rounded bg-white shadow-sm flex-grow-1"
                                style="max-height: calc(99vh - 200px); overflow-y: auto;">
                                <table class="table table-hover table-sm mb-0 align-middle" style="font-size: 11px;">
                                    <thead class="bg-dark text-white sticky-top text-center" style="z-index:2;">
                                        <tr>
                                            <th style="width:4%">NO</th>
                                            <th class="text-start">ITEM</th>
                                            <th class="text-start">DESC</th>
                                            <th>COUNTED</th>
                                            <th>ON-HAND</th>
                                            <th>QTY</th>
                                            <th style="width:15%">PROGRESS</th>
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

    {{-- MODAL 6: DETAIL SCAN HISTORY (CNTSO) --}}
    <div class="modal fade" id="modalScanHistory" tabindex="-1" style="z-index: 1060;" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white d-flex align-items-center" style="background-color: #334155;">
                    <h6 class="modal-title fw-bold mb-0" id="modalTitleScanHistory">DETAIL SCAN: </h6>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0 bg-light" id="modalScanHistoryContent"></div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/appkso/dashboard_so_auto.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Inisialisasi ikon Lucide
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Inisialisasi Tooltip Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
@endsection
