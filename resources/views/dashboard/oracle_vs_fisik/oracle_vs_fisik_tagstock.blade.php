<div class="col-12 h-100 d-flex flex-column bg-white border rounded-3 p-3 shadow-sm"
    style="font-size: 11px; overflow: hidden;">

    <div class="w-100 d-flex align-items-center justify-content-between pb-3 mb-2 border-bottom flex-shrink-0 no-print">

        <div class="d-flex align-items-center gap-2">
            <select id="tag-filter-wh" class="form-select form-select-sm fw-bold border-primary" style="width: 160px;">
                <option value="" selected>⏳ MEMUAT GUDANG...</option>
            </select>

            <select id="tag-filter-operator" class="form-select form-select-sm fw-bold border-secondary"
                style="width: 250px;" disabled>
                <option value="" selected>-- KUNCI OPERATOR --</option>
            </select>
            <button type="button" id="btn-print-massal-tag"
                class="btn btn-sm btn-success fw-bold text-uppercase px-3 shadow-sm d-none d-flex align-items-center"
                onclick="openTagStockPrintEngine()">
                <i data-lucide="printer" class="me-1" style="width: 14px; height: 14px;"></i> Print Rekap Tag Stock
            </button>
        </div>
        <div class="d-flex justify-content-end align-items-center gap-1 d-none" id="doc-filter-container">
            <select id="tag-filter-doc-start" class="form-select form-select-sm fw-bold border-info"
                style="width: 180px;">
                <option value="">-- DOC AWAL --</option>
            </select>
            <select id="tag-filter-doc-end" class="form-select form-select-sm fw-bold border-info"
                style="width: 180px;">
                <option value="">-- DOC AKHIR --</option>
            </select>

            <form id="form-tagstock" method="POST" action="/oracle-fisik/tagstock/print" target="_blank">
                @csrf

                <input type="hidden" name="warehouse" id="f-wh">
                <input type="hidden" name="operator_id" id="f-op">
                <input type="hidden" name="doc_start" id="f-doc-start">
                <input type="hidden" name="doc_end" id="f-doc-end">

                <div class="d-flex gap-1">
                    <!-- Tombol Tag Stock -->
                    <button type="button" id="btn-tag-stock"
                        class="btn btn-sm btn-primary fw-bold text-uppercase px-3 shadow-sm d-flex align-items-center">
                        <i data-lucide="printer" class="me-1" style="width: 14px; height: 14px;"></i> TAG STOCK
                    </button>

                    <!-- Tombol Reset -->
                    <button type="button" id="btn-reset-filter"
                        class="btn btn-sm btn-danger fw-bold text-uppercase px-3 shadow-sm d-flex align-items-center"
                        onclick="resetFilters()">
                        <i data-lucide="rotate-ccw" class="me-1" style="width: 14px; height: 14px;"></i> RESET
                    </button>
                </div>
            </form>

        </div>
    </div>

    <div id="print-header-laporan" class="d-none w-100 mb-3 text-dark">
        <h2 class="fw-bold mb-3 text-uppercase"
            style="font-size: 18px; border-bottom: 2px solid #000; padding-bottom: 5px; letter-spacing: 0.5px;">
            Monitoring Stock (<span id="print-wh-title">-</span>)</h2>
        <table style="width: 100%; border: none !important; margin-bottom: 15px; font-size: 13px;">
            <tr style="border: none !important;">
                <td style="width: 10%; text-align: left; font-weight: bold; border: none !important; padding: 2px 0;">
                    PIC</td>
                <td style="width: 90%; text-align: left; border: none !important; padding: 2px 0;">: <span
                        id="print-pic-name" class="fw-bold">-</span></td>
            </tr>
            <tr style="border: none !important;">
                <td style="text-align: left; font-weight: bold; border: none !important; padding: 2px 0;">Area</td>
                <td style="text-align: left; border: none !important; padding: 2px 0;">: <span id="print-area-lot"
                        class="fw-bold">-</span></td>
            </tr>
        </table>
    </div>

    <div class="flex-grow-1 position-relative" style="overflow: hidden;">
        <div class="table-responsive h-100" id="print-scroll-box"
            style="overflow-y: auto; max-height: calc(100vh - 240px);">
            <table class="table table-sm table-hover align-middle mb-0" id="table-view-tagstock-list"
                style="width: 100%; border-collapse: collapse;">

                <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                    <tr>
                        <th width="5%" class="text-center bg-light border-dark py-2">No.</th>
                        <th width="12%" class="bg-light border-dark">Lot</th>
                        <th width="12%" class="bg-light border-dark">No. Doc</th>
                        <th width="12%" class="bg-light border-dark">Item</th>
                        <th class="bg-light border-dark text-start">Deskripsi Master Size</th>
                        <th width="12%" class="text-center bg-light border-dark">Jumlah Rak</th>
                        <th width="12%" class="text-end bg-light border-dark">Qty</th>
                        <th width="12%" class="text-end bg-light border-dark">Jumlah Aktual</th>
                    </tr>
                </thead>

                <tbody id="tbody-tagstock-rows">
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5 border-0">Silakan pilih target gudang
                            dan
                            operator di atas untuk memilah baris area bro.</td>
                    </tr>
                </tbody>

                <tfoot id="tfoot-tagstock-summary"
                    class="table-light fw-bold text-uppercase position-sticky bottom-0 d-none"
                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 2px 0 #212529, inset 0 -1px 0 #dee2e6;">
                    <tr>
                        <td colspan="5" class="text-end py-2 text-dark bg-light border-dark">TOTAL RINGKASAN
                            PENUGASAN :</td>
                        <td id="total-summary-rack"
                            class="text-center text-danger font-monospace bg-light border-dark">
                            0 RAK</td>
                        <td id="total-summary-qty" class="text-end text-primary font-monospace bg-light border-dark">0
                            PCS</td>
                        <td class="bg-light border-dark"></td>
                    </tr>
                </tfoot>

            </table>
        </div>
    </div>
</div>
