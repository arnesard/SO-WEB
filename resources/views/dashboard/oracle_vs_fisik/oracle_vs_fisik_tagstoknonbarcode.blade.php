{{-- TAG STOCK NON-BARCODE — Layout mirip APPKSO: Upload kiri, Data kanan --}}
<div class="container-fluid p-0 d-flex flex-column gap-1 text-dark" style="font-size: 11px;">

    <div class="row g-1 mt-1">

        {{-- 📥 PANEL UPLOAD KIRI (col-xl-2) --}}
        <div class="col-12 col-xl-2 d-flex flex-column">
            <div class="card border border-warning shadow-sm p-3 text-center h-100 d-flex flex-column justify-content-center bg-white"
                style="border-radius: 12px; min-height: 280px;">

                <div class="p-2 rounded-circle bg-warning bg-opacity-10 mx-auto mb-2 d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px;">
                    <i data-lucide="file-spreadsheet" style="width: 22px; height: 22px; color: #fe6807;"></i>
                </div>

                <h6 class="text-uppercase fw-bold text-dark mb-1"
                    style="letter-spacing: 0.5px; font-weight: 900; font-size: 12px;">
                    Upload Non-Barcode
                </h6>
                <p class="text-muted mb-2" style="font-size: 10px; line-height: 12px;">
                    Unggah file <strong>.xlsx</strong> Tag Stock Non-Barcode.
                </p>

                <div class="text-start mb-2">
                    <select id="nonbarcode-wh" class="form-select form-select-sm fw-bold border-warning"
                        style="font-size: 11px;" required>
                        <option value="" disabled selected>-- PILIH GUDANG --</option>
                        <option value="APW">APW</option>
                        <option value="BPW">BPW</option>
                        <option value="DPW">DPW</option>
                        <option value="RPW">RPW</option>
                    </select>
                </div>

                <div class="border border-2 border-dashed rounded-3 p-3 bg-light position-relative mb-2"
                    style="border-color: #fe6807 !important;">
                    <input type="file" id="nonbarcode-file" name="nonbarcode_file"
                        class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;"
                        accept=".xlsx,.xls">
                    <i data-lucide="upload-cloud" class="text-muted mb-1" style="width: 20px; height: 20px;"></i>
                    <div class="small fw-bold text-secondary text-truncate px-2" id="text-nonbarcode-file"
                        style="font-size: 10px;">
                        Klik atau seret file Excel ke sini
                    </div>
                </div>

                <button type="button" id="btn-upload-nonbarcode"
                    class="btn w-100 btn-sm fw-bold py-2 rounded-pill shadow-sm text-uppercase text-white"
                    style="font-size: 11px; letter-spacing: 0.5px; background-color: #fe6807; border: none; display: flex; align-items: center; justify-content: center; gap: 4px; margin-bottom: 8px;">
                    <i data-lucide="file-check" style="width: 14px; height: 14px;"></i> Proses Upload Data
                </button>

                <a href="{{ asset('template/tes tag stok kosong.xlsx') }}" target="_blank"
                    class="btn w-100 btn-sm fw-bold py-2 rounded-pill shadow-sm text-uppercase text-white"
                    style="font-size: 11px; letter-spacing: 0.5px; background-color: #2a5ae0; border: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                    File Contoh Upload
                </a>
            </div>
        </div>

        {{-- 📊 PANEL KANAN: Filter + Tabel Data (col-xl-10) --}}
        <div class="col-12 col-xl-10 d-flex flex-column gap-1">

            {{-- 🛠️ CARD FILTER BARIS ATAS --}}
            <div class="card border border-secondary shadow-sm p-2 bg-white d-flex flex-row align-items-center justify-content-between flex-wrap gap-1"
                style="border-radius: 8px;">

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <select id="tag-filter-wh" class="form-select form-select-sm fw-bold border-primary shadow-sm"
                        style="width: 160px; font-size: 11px; height: 28px;">
                        <option value="" selected>⏳ MEMUAT GUDANG...</option>
                    </select>

                    <select id="tag-filter-operator" class="form-select form-select-sm fw-bold border-secondary"
                        style="width: 240px; font-size: 11px; height: 28px;" disabled>
                        <option value="" selected>-- PILIH OPERATOR --</option>
                    </select>
                </div>

                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <div id="doc-filter-container" class="d-flex align-items-center gap-1 d-none">
                        <select id="tag-filter-doc-start" class="form-select form-select-sm fw-bold border-info"
                            style="width: 165px; font-size: 11px; height: 28px;">
                            <option value="">-- DOC AWAL --</option>
                        </select>
                        <select id="tag-filter-doc-end" class="form-select form-select-sm fw-bold border-info"
                            style="width: 165px; font-size: 11px; height: 28px;">
                            <option value="">-- DOC AKHIR --</option>
                        </select>
                    </div>

                    <button type="button" id="btn-print-massal-tag"
                        class="btn btn-xs btn-success fw-bold text-uppercase d-none" onclick="openTagStockPrintEngine()"
                        style="font-size: 10px; height: 28px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="printer" style="width: 12px; height: 12px;"></i> Print Rekap
                    </button>

                    <button type="button" id="btn-tag-stock" class="btn btn-xs btn-primary fw-bold text-uppercase"
                        style="font-size: 10px; height: 28px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="printer" style="width: 12px; height: 12px;"></i> TAG STOCK
                    </button>

                    <button type="button" id="btn-reset-filter" class="btn btn-xs btn-danger fw-bold text-uppercase"
                        onclick="resetFilters()"
                        style="font-size: 10px; height: 28px; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i> RESET
                    </button>
                </div>
            </div>

            {{-- 📋 CARD TABEL DATA --}}
            <div class="card border border-primary shadow-sm bg-white d-flex flex-column"
                style="border-radius: 12px; overflow: hidden; flex: 1;">

                <div
                    class="d-flex justify-content-between align-items-center border-bottom pb-2 pt-3 px-3 mb-0 flex-shrink-0">
                    <h6 class="fw-bold text-uppercase mb-0 text-primary"
                        style="font-size: 11px; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="table-properties" style="width: 14px; height: 14px;"></i> Data Tag Stock
                        Non-Barcode
                    </h6>
                    <div id="tfoot-tagstock-summary" class="d-none d-flex align-items-center gap-2"
                        style="font-size: 10px;">
                        <span class="badge bg-secondary text-white px-2">Total Rak:
                            <span id="total-summary-rack" class="fw-bold">0</span>
                        </span>
                        <span class="badge bg-primary text-white px-2">Total Qty:
                            <span id="total-summary-qty" class="fw-bold">0</span>
                        </span>
                    </div>
                </div>

                <div class="table-responsive px-3 pb-3" style="overflow-y: auto; max-height: calc(100vh - 260px);">
                    <table class="table table-sm table-hover align-middle mb-0" id="table-view-tagstock-list"
                        style="width: 100%; border-collapse: collapse;">

                        <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                            style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                            <tr>
                                <th width="4%" class="text-center bg-light border-dark py-2">No.</th>
                                <th width="11%" class="bg-light border-dark">Lot</th>
                                <th width="13%" class="bg-light border-dark">No. Doc</th>
                                <th width="12%" class="bg-light border-dark">Item</th>
                                <th class="bg-light border-dark text-start">Deskripsi Master Size</th>
                                <th width="10%" class="text-center bg-light border-dark">Jml Rak</th>
                                <th width="10%" class="text-end bg-light border-dark">Qty</th>
                                <th width="10%" class="text-end bg-light border-dark">Jml Aktual</th>
                            </tr>
                        </thead>

                        <tbody id="tbody-tagstock-rows">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5 border-0">
                                    Silakan pilih gudang dan operator untuk menampilkan data.
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>

        </div>{{-- end col kanan --}}
    </div>{{-- end row --}}

    {{-- Hidden form untuk print (diperlukan JS) --}}
    <form id="form-tagstock" method="POST" action="/oracle-fisik/tagstock-nonbarcode/print" target="_blank"
        class="d-none">
        @csrf
        <input type="hidden" name="warehouse" id="f-wh">
        <input type="hidden" name="operator_id" id="f-op">
        <input type="hidden" name="doc_start" id="f-doc-start">
        <input type="hidden" name="doc_end" id="f-doc-end">
    </form>

</div>

<script>
    // Update nama file saat dipilih
    document.getElementById('nonbarcode-file').addEventListener('change', function() {
        const label = document.getElementById('text-nonbarcode-file');
        label.textContent = this.files[0] ? this.files[0].name : 'Klik atau seret file Excel ke sini';
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
