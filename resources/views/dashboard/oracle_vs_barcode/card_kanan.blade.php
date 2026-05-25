<div class="col-md-9 d-flex flex-column h-100" style="min-height: 0;">
    <div class="card border shadow-sm d-flex flex-column h-100" style="overflow: hidden; border-radius: 10px;">
        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i data-lucide="bar-chart-big" class="text-primary" style="width: 16px;"></i>
                <span class="small fw-bold uppercase">Comparison: Oracle vs Barcode</span>
            </div>
        </div>

        <div
            class="card-body p-3 flex-grow-1 overflow-auto custom-scroll bg-white position-relative d-flex flex-column gap-3">
            {{-- Loader --}}
            <div id="chart-loader" class="d-none position-absolute top-50 start-50 translate-middle text-center"
                style="z-index: 100;">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="small mt-0 fw-bold">Loading Data...</div>
            </div>

            {{-- Card untuk Grafik (Sekarang pakai Border dan Shadow Halus) --}}
            <div id="chart-container" class="card border shadow-sm d-none"
                style="background-color: #fcfcfc; border-radius: 8px; flex-shrink: 0;">
                <div class="card-body p-2">
                    <div id="main-chart" class="w-100" style="height: 250px;"></div>
                </div>
            </div>

            {{-- TEMPAT CARD BARU LU DI BAWAH GRAFIK --}}
            <div id="additional-info-area" class="d-none">
            </div>
        </div>
    </div>
</div>
