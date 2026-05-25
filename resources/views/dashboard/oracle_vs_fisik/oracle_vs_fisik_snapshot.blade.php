<div class="col-12 h-100 d-flex flex-column flex-xl-row gap-3">

    {{-- BAGIAN KIRI: UPLOAD FORM --}}
    <div class="flex-shrink-0 d-flex flex-column justify-content-center"
        style="width: 100%; xl-max-width: 380px; max-width: 400px; min-width: 320px;">
        <div class="card border-0 shadow-sm p-4 text-center h-100 d-flex flex-column justify-content-center"
            style="border-radius: 12px; background: #ffffff;">
            <div class="p-3 rounded-circle bg-success bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center"
                style="width: 65px; height: 65px;">
                <i data-lucide="file-up" style="width: 28px; height: 28px;" class="text-success"></i>
            </div>
            <h6 class="fw-black text-dark mb-1 text-uppercase" style="letter-spacing: 0.5px;">Upload Oracle Snapshot
            </h6>
            <p class="text-muted mb-3" style="font-size: 11px; line-height: 14px;">Unggah berkas <strong>.xlsx</strong>
                hasil tarikan dari Oracle.</p>

            <form id="form-upload-snapshot" onsubmit="event.preventDefault();">
                @csrf
                <div class="text-start mb-3">
                    <label class="fw-bold mb-1 text-secondary" style="font-size: 11px;">Target Warehouse</label>
                    <select id="upload-target-wh" name="target_warehouse"
                        class="form-select form-select-sm fw-bold border-success" required>
                        <option value="" disabled selected>-- PILIH WAREHOUSE TARGET --</option>
                        <option value="APW">APW</option>
                        <option value="BPW">BPW</option>
                        <option value="DPW">DPW</option>
                        <option value="RPW">RPW</option>
                    </select>
                </div>

                <div class="border border-2 border-dashed rounded-3 p-4 bg-light position-relative transition-all"
                    style="border-color: #28a745 !important;">
                    <input type="file" id="file-excel" name="file_excel"
                        class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;"
                        accept=".xlsx, .xls" required>
                    <i data-lucide="upload-cloud" class="text-muted mb-2" style="width: 24px; height: 24px;"></i>
                    <div class="small fw-bold text-secondary text-truncate px-2" id="text-file-excel"
                        style="font-size: 11px;">Klik atau seret file Excel ke sini</div>
                </div>
                <button type="submit" id="btn-submit-upload"
                    class="btn btn-sm btn-success w-100 fw-bold mt-3 py-2 rounded-pill shadow-sm text-uppercase"
                    style="font-size: 11px; letter-spacing: 0.5px;">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Proses Import Data
                </button>
            </form>
        </div>
    </div>

    {{-- BAGIAN KANAN: TABEL DATA --}}
    <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm d-flex flex-column h-100" style="overflow: hidden;">
        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 flex-shrink-0">
            <div class="d-flex align-items-center gap-2">
                <select id="filter-snapshot-wh" class="form-select form-select-sm fw-bold border-primary"
                    style="width: 200px;" onchange="window.loadSnapshotData()">
                    <option value="" selected>⏳ MEMUAT GUDANG...</option>
                </select>
            </div>

            <div class="input-group input-group-sm" style="max-width: 420px; width: 100%;">
                <span class="input-group-text bg-white border-end-0"><i
                        class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="search-snapshot-item"
                    class="form-control form-control-sm border-start-0 border-end-0 ps-0"
                    style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()"
                    placeholder="Cari item kode atau deskripsi...">

                <button class="btn btn-primary fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                    type="button" id="btn-submit-search" onclick="window.loadSnapshotData()" style="font-size: 10px;">
                    <i data-lucide="search" style="width: 12px; height: 12px;"></i> CARI
                </button>

                <button class="btn btn-dark fw-bold px-3 d-flex align-items-center gap-1 text-uppercase" type="button"
                    onclick="document.getElementById('search-snapshot-item').value=''; document.getElementById('filter-snapshot-wh').value=''; window.loadSnapshotData();"
                    style="font-size: 10px;">
                    <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i> RESET
                </button>
            </div>
        </div>

        <div class="flex-grow-1 position-relative" style="overflow: hidden;">
            <div class="table-responsive h-100" style="overflow-y: auto; max-height: calc(100vh - 250px);">
                <table class="table table-sm table-hover align-middle mb-0" id="table-snapshot"
                    style="font-size: 11px;">
                    <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                        style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                        <tr>
                            <th class="bg-light text-center" width="5%">No.</th>
                            <th class="bg-light text-center" width="10%">WH</th>
                            <th class="bg-light" width="20%">Item Code</th>
                            <th class="bg-light" width="45%">Description (From Master)</th>
                            <th class="bg-light text-end" width="20%">Qty Oracle</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-snapshot">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Memuat data...</td>
                        </tr>
                    </tbody>
                    {{-- 🎯 TAMBAHAN FOOTER TOTAL TETAP MUNCUL DI BAWAH (STICKY) --}}
                    <tfoot class="table-light fw-bold text-uppercase position-sticky bottom-0"
                        style="z-index: 5; box-shadow: inset 0 1px 0 #dee2e6;">
                        <tr>
                            <td colspan="4" class="text-end pe-3 text-secondary">
                                Menampilkan: <span id="summary-total-rows"
                                    class="text-danger ms-1 font-monospace">0</span> ITEM SKU
                            </td>
                            <td class="text-end text-primary font-monospace" id="summary-total-qty"
                                style="font-size: 13px;">0 PCS</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
