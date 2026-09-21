<div class="col-12 h-100 d-flex flex-column">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-shrink-0">
        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-table me-2"></i>Data Master Size</h6>

        <div class="d-flex align-items-center gap-2">
            <select id="filter-wh" class="form-select form-select-sm fw-bold border-primary" style="width: 140px;"
                onchange="window.filterMasterSizeTable()">
                <option value="" selected>⚠️ PILIH GUDANG</option>
            </select>

            <select id="filter-grade" class="form-select form-select-sm" style="width: 110px;"
                onchange="window.filterMasterSizeTable()">
                <option value="">ALL GRADE</option>
            </select>

            <div class="input-group input-group-sm" style="width: 220px;">
                <span class="input-group-text bg-white border-end-0"><i
                        class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="search-master-size" class="form-control form-control-sm border-start-0 ps-0"
                    placeholder="Cari item / deskripsi..." onkeyup="window.filterMasterSizeTable()">
            </div>

            <button type="button" class="btn btn-xs btn-dark fw-bold px-3 py-1.5 rounded-pill shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modalMasterSize" onclick="window.resetFormSize()">
                <i class="fa-solid fa-plus me-1"></i> TAMBAH ITEM
            </button>
        </div>
    </div>

    {{-- 🔒 PERBAIKAN SCROLL: Mengunci kepala tabel (Sticky Head) dengan membuang pembatas overflow luar --}}
    <div class="flex-grow-1 bg-white border rounded-3 p-0 shadow-sm position-relative" style="overflow: hidden;">
        <div class="table-responsive h-100" style="overflow-y: auto; max-height: calc(100vh - 250px);">
            <table class="table table-sm table-hover align-middle mb-0" id="table-master-size" style="font-size: 11px;">
                <thead class="table-light text-uppercase fw-bold position-sticky top-0 style-sticky-head"
                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                    <tr>
                        <th class="bg-light">No.</th>
                        <th class="bg-light">Warehouse</th>
                        <th class="bg-light">Item</th>
                        <th class="bg-light">Description</th>
                        <th class="bg-light">Grade</th>
                        <th class="bg-light">Product</th>
                        <th class="bg-light">Type</th>
                        <th class="bg-light">Brand</th>
                        <th class="bg-light">Category</th>
                        <th class="bg-light">Pattern</th>
                        <th width="8%" class="text-center bg-light">Action</th>
                    </tr>
                </thead>
                <tbody id="tbody-master-size">
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- DAFTAR PILIHAN OTOMATIS (DATALIST) UNTUK MODEL BEBAS KETIK / PILIH --}}
<datalist id="list-product">
    <option value="TIRE"></option>
    <option value="TUBE"></option>
    <option value="VALVE"></option>
    <option value="RIMBAND"></option>
</datalist>

<datalist id="list-type">
    <option value="TUBELESS"></option>
    <option value="TUBETYPE"></option>
    <option value="-"></option>
</datalist>

<datalist id="list-brand">
    <option value="IRC"></option>
    <option value="GT"></option>
    <option value="ZENEOS"></option>
    <option value="-"></option>
</datalist>

<datalist id="list-category">
    <option value="REGULER"></option>
    <option value="SPAREPART"></option>
    <option value="IMPORT"></option>
    <option value="EXPORT"></option>
    <option value="-"></option>
</datalist>

{{-- MODAL INPUT & EDIT DATA --}}
<div class="modal fade" id="modalMasterSize" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-dark text-white py-2.5">
                <h6 class="modal-title fw-bold text-uppercase" id="modalTitleSize">Tambah Master Size</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-master-size" onsubmit="return false;">
                @csrf
                <input type="hidden" id="size-id">
                <div class="modal-body p-3" style="font-size: 12px;">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="fw-bold mb-1">Warehouse</label>
                            <select id="size-warehouse" class="form-select form-select-sm" required>
                                <option value="" disabled selected>Pilih WH...</option>
                                <option value="APW">APW</option>
                                <option value="BPW">BPW</option>
                                <option value="DPW">DPW</option>
                                <option value="RPW">RPW</option>
                                <option value="JMW">JMW</option>
                                <option value="DCK">DCK</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold mb-1">Item Code</label>
                            <input type="text" id="size-item" class="form-control form-control-sm text-uppercase"
                                required placeholder="CONTOH: PXF2514-0" autocomplete="off"
                                onkeyup="window.calculateAutoGrade(this.value)">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold mb-1">Grade</label>
                            <input type="text" id="size-grade"
                                class="form-control form-control-sm bg-light fw-bold text-center" readonly required
                                placeholder="Otomatis terisi...">
                        </div>
                        <div class="col-md-12">
                            <label class="fw-bold mb-1">Description</label>
                            <input type="text" id="size-description"
                                class="form-control form-control-sm text-uppercase" required
                                placeholder="MASUKKAN DESKRIPSI BARANG..." autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold mb-1">Product</label>
                            <input type="text" id="size-product"
                                class="form-control form-control-sm text-uppercase" list="list-product"
                                placeholder="PILIH / KETIK...">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold mb-1">Type</label>
                            <input type="text" id="size-type" class="form-control form-control-sm text-uppercase"
                                list="list-type" placeholder="PILIH / KETIK...">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold mb-1">Brand</label>
                            <input type="text" id="size-brand" class="form-control form-control-sm text-uppercase"
                                list="list-brand" placeholder="PILIH / KETIK...">
                        </div>
                        <div class="col-md-3">
                            <label class="fw-bold mb-1">Category</label>
                            <input type="text" id="size-category"
                                class="form-control form-control-sm text-uppercase" list="list-category"
                                placeholder="PILIH / KETIK...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-xs btn-secondary fw-bold px-3"
                        data-bs-dismiss="modal">BATAL</button>
                    <button type="button" class="btn btn-xs btn-primary fw-bold px-4"
                        onclick="window.executeSaveMasterSize()">SIMPAN DATA</button>
                </div>
            </form>
        </div>
    </div>
</div>
