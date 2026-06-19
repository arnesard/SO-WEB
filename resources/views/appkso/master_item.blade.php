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
            height: calc(110vh - 70px);
            padding-bottom: 20px;
        }

        .bg-orange {
            background-color: #fe6807 !important;
        }

        .text-orange {
            color: #fe6807 !important;
        }

        .border-orange {
            border-color: #fe6807 !important;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #fe6807;
            border-radius: 10px;
        }
    </style>

    <div class="container-fluid fixed-wrapper">
        <div class="card shadow-sm border-0 d-flex flex-column h-100"
            style="border: 1px solid #fe6807 !important; border-radius: 12px; overflow: hidden;">

            {{-- HEADER --}}
            <div class="card-header py-2 d-flex justify-content-between align-items-center"
                style="background-color: #fe6807; color: #fff; border-radius: 11px 11px 0 0; flex-shrink: 0;">

                {{-- Title: Dibuat flex-shrink-0 supaya tulisannya nggak kegencet --}}
                <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center flex-shrink-0 me-3">
                </h5>

                <div style="width: 50%; text-align: right;" class="d-flex justify-content-end align-items-center gap-1">
                    @include('appkso.navbar')
                </div>
            </div>

            {{-- SECTION 2: MASTER DATA --}}
            <div id="master-section" class="content-section" style="height: calc(10vh - 170px); min-height: 640px;">
                <div class="card shadow-sm border-0 d-flex flex-column h-100"
                    style="border: 1px solid #0d6efd !important; border-radius: 2px; overflow: hidden;">

                    {{-- Header Master Data --}}
                    <div class="card-header py-2 d-flex justify-content-between align-items-center"
                        style="background-color: #0d6efd; color: #fff; ">
                        <h5 class="card-title mb-0 small fw-bold text-uppercase d-flex align-items-center">
                            <i data-lucide="clipboard-check" class="me-2" style="width: 16px; height: 16px;"></i>
                            Master Item Gudang Ban B
                        </h5>

                        <div class="d-flex gap-2">
                            <div class="input-group input-group-sm shadow-sm"
                                style="width: 250px; border-radius: 6px; overflow: hidden;">
                                <span class="input-group-text bg-white border-0 text-primary">
                                    <i data-lucide="search" style="width: 14px; height: 14px;"></i>
                                </span>
                                <input type="text" id="searchMaster" class="form-control border-0"
                                    placeholder="Cari item..." onkeyup="filterMasterTable()">
                            </div>

                            <button class="btn btn-sm btn-light text-primary fw-bold shadow-sm px-3"
                                onclick="openAddModal()" style="border-radius: 6px;">
                                <i data-lucide="plus-circle" class="me-1" style="width: 14px; height: 14px;"></i>
                                Tambah Item
                            </button>

                            <button class="btn btn-sm btn-warning text-dark fw-bold shadow-sm px-3"
                                onclick="openSimilarModal()" style="border-radius: 6px;">
                                <i data-lucide="git-merge" class="me-1" style="width: 14px; height: 14px;"></i>
                                Item Similar
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
                                        <input type="text" name="item_code" id="m_item_code" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-danger">Item Code Desc (Unique)*</label>
                                        <input type="text" name="item_code_desc" id="m_item_code_desc"
                                            class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Description (Report)</label>
                                        <input type="text" name="description" id="m_description" class="form-control">
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
                                            class="form-control border-primary" list="list-type"
                                            placeholder="Pilih/Ketik..." required>
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
            {{-- ============================================================ --}}
            {{-- ★ MODAL ITEM SIMILAR (BARU) - Extra Large                    --}}
            {{-- ============================================================ --}}
            <div class="modal fade" id="modalItemSimilar" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow">

                        {{-- Header --}}
                        <div class="modal-header py-2" style="background-color: #fd7e14;">
                            <h5 class="modal-title fw-bold text-uppercase text-white small d-flex align-items-center gap-2"
                                id="similarModalTitle">
                                <i data-lucide="git-merge" style="width:15px;height:15px;"></i>
                                Manajemen Item Similar
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-0" style="background:#f8f9fa;">
                            <div class="row g-0 h-100">

                                {{-- ★ PANEL KIRI: Form Tambah / Edit --}}
                                <div class="col-md-4 border-end p-3" style="background:#fff;">
                                    <p class="small fw-bold text-muted text-uppercase mb-2">
                                        <i data-lucide="link" style="width:13px;height:13px;" class="me-1"></i>
                                        Form Relasi Similar
                                    </p>

                                    <form id="formItemSimilar">
                                        @csrf
                                        <input type="hidden" id="s_similar_id" name="id">

                                        {{-- ItemCode Utama — dari master_items.item_code_desc --}}
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-dark mb-1">
                                                Item Code Utama <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="s_item_code" name="ItemCode"
                                                class="form-control form-control-sm fw-bold" list="datalist-itemcode-main"
                                                placeholder="Ketik / Pilih item code desc..."
                                                oninput="onItemCodeChange('s_item_code','main')" autocomplete="off"
                                                required>
                                            <datalist id="datalist-itemcode-main"></datalist>
                                            <div class="preview-box mt-1" id="preview-main">
                                                <span class="text-muted fst-italic" style="font-size:10px;">Pilih item
                                                    dari daftar...</span>
                                            </div>
                                        </div>

                                        {{-- ItemCode Similar — free text, no datalist --}}
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-primary mb-1">
                                                Item Code Similar <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="s_item_code_similar" name="ItemCodeSimilar"
                                                class="form-control form-control-sm fw-bold border-primary"
                                                placeholder="Ketik item code similar..." autocomplete="off" required>
                                            {{-- Tidak ada datalist, tidak ada preview --}}
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button type="submit" class="btn btn-warning btn-sm fw-bold flex-grow-1"
                                                id="btnSaveSimilar">
                                                <i class="fa-solid fa-save me-1"></i> SIMPAN SIMILAR
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="resetSimilarForm()" title="Reset form">
                                                <i data-lucide="rotate-ccw" style="width:13px;height:13px;"></i>
                                            </button>
                                        </div>
                                    </form>

                                    {{-- Info relasi tabel --}}
                                    <hr class="my-3">
                                    <div class="rounded p-2"
                                        style="background:#fff8f0; border:1px dashed #fd7e14; font-size:10px;">
                                        <p class="fw-bold text-orange mb-1">
                                            <i data-lucide="info" style="width:12px;height:12px;" class="me-1"></i>
                                            Info Relasi Tabel
                                        </p>
                                        <ul class="mb-0 ps-3 text-muted">
                                            <li><b>master_items</b> → sumber ItemCode & deskripsi</li>
                                            <li><b>snapshot_bpw</b> → stok BPW per ItemCode</li>
                                            <li><b>cntso</b> → qty SO per ItemCode</li>
                                            <li>Relasi <b>A↔B</b> bersifat dua arah (duplikat dicegah)</li>
                                        </ul>
                                    </div>
                                </div>

                                {{-- ★ PANEL KANAN: Tabel Data Similar --}}
                                <div class="col-md-8 p-3 d-flex flex-column" style="min-height: 500px;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="small fw-bold text-muted text-uppercase mb-0">
                                            <i data-lucide="table-2" style="width:13px;height:13px;" class="me-1"></i>
                                            Data Item Similar
                                        </p>
                                        <div class="input-group input-group-sm shadow-sm"
                                            style="width: 220px; border-radius: 6px; overflow: hidden;">
                                            <span class="input-group-text bg-white border-0 text-warning">
                                                <i data-lucide="search" style="width: 13px; height: 13px;"></i>
                                            </span>
                                            <input type="text" id="searchSimilar" class="form-control border-0"
                                                placeholder="Cari item code..." onkeyup="filterSimilarTable()">
                                        </div>
                                    </div>

                                    <div class="table-responsive flex-grow-1 custom-scroll"
                                        style="overflow-y: auto; max-height: 460px;">
                                        <table class="table table-sm table-bordered table-hover mb-0"
                                            style="font-size: 10px;">
                                            <thead class="sticky-top"
                                                style="z-index: 10; background: #fd7e14; color:#fff;">
                                                <tr class="text-center align-middle">
                                                    <th style="width:35px;">No</th>
                                                    <th>Item Code Utama</th>
                                                    <th>Item Code Similar</th>
                                                    <th style="width:80px;">Dibuat</th>
                                                    <th style="width:70px;">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody id="similarTableBody">
                                                <tr>
                                                    <td colspan="9" class="text-center py-4 text-muted">
                                                        <span class="spinner-border spinner-border-sm"></span> Loading...
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-2 small text-muted fw-bold">
                                        <span id="similarRowCountInfo">Total: 0 Pasang</span>

                                    </div>
                                </div>

                            </div>{{-- end row --}}
                        </div>{{-- end modal-body --}}

                    </div>
                </div>
            </div>
            {{-- END MODAL ITEM SIMILAR --}}
        </div>
    </div>

    @push('scripts')
        <script>
            // Definisikan route tujuan untuk dipakai oleh Javascript
            window.appRoutes = {
                master_list: "{{ route('appkso.master_item.data') }}",
                similar_list: "{{ route('appkso.similar_item.data') }}",
                similar_itemcode_list: "{{ route('appkso.master_item.itemcode_list') }}",
            };
        </script>
        <script src="{{ asset('js/appkso/master_item.js') }}"></script>
    @endpush
@endsection
