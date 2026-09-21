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
                    <i data-lucide="clipboard-check" class="me-2" style="width: 16px; height: 16px;"></i>
                    Master PIC Stock Opname
                </h5>

                <div style="width: 50%; text-align: right;" class="d-flex justify-content-end align-items-center gap-1">
                    @include('appkso.navbar')
                </div>
            </div>

            <div class="col-12 h-100 d-flex flex-column flex-xl-row gap-3 animate__animated animate__fadeIn"
                style="font-size: 11px;">

                <div class="flex-shrink-0 d-flex flex-column justify-content-center"
                    style="width: 100%; xl-max-width: 380px; max-width: 400px; min-width: 320px;">
                    <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-center bg-white"
                        style="border-radius: 12px;">

                        <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-2">
                            <i data-lucide="user-plus" class="text-primary" style="width: 22px; height: 22px;"></i>
                            <h6 class="fw-black text-dark mb-0 text-uppercase" style="letter-spacing: 0.5px;">Registrasi PIC
                                Area
                            </h6>
                        </div>

                        <form id="form-manage-master-pic">
                            <input type="hidden" id="pic-entry-id" value="">

                            <div class="mb-2 text-start">
                                <label class="fw-bold mb-1 text-secondary">Tipe Penugasan / Role Tim</label>
                                <select id="pic-role-type" class="form-select form-select-sm fw-bold border-primary"
                                    required>
                                    <option value="STOCK" selected>📦 TIM PENGHITUNG FISIK (STOCK)</option>
                                    <option value="AUDITOR">🔍 TIM VALIDATOR (AUDITOR)</option>
                                </select>
                            </div>

                            <div class="mb-2 text-start">
                                <label class="fw-bold mb-1 text-secondary">Warehouse Area</label>
                                <select id="pic-warehouse" class="form-select form-select-sm fw-bold border-secondary"
                                    required>
                                    <option value="APW">APW</option>
                                    <option value="BPW" selected>BPW</option>
                                    <option value="DPW">DPW</option>
                                    <option value="RPW">RPW</option>
                                    <option value="DCK">DCK</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-2 text-start">
                                <div class="col-6">
                                    <label class="fw-bold mb-1 text-secondary">No. Penneng / id</label>
                                    <input type="text" id="pic-penneng"
                                        class="form-control form-control-sm fw-bold text-uppercase"
                                        placeholder="Contoh : 10-0453" required>
                                </div>
                                {{-- BARU --}}
                                <div class="col-6">
                                    <label class="fw-bold mb-1 text-secondary">Gedung</label>
                                    <select id="pic-gedung"
                                        class="form-select form-select-sm fw-bold text-uppercase border-secondary" required>
                                        <option value="">--Pilih Gedung--</option>
                                        <option value="BPW01">BPW01</option>
                                        <option value="BPW02">BPW02</option>
                                        <option value="BPW03">BPW03</option>
                                        <option value="BPW04">BPW04</option>
                                        <option value="DCK01">DCK01</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2 text-start">
                                <label class="fw-bold mb-1 text-secondary">Nama Lengkap Personel</label>
                                <input type="text" id="pic-nama" class="form-control form-control-sm"
                                    placeholder="Masukkan nama lengkap..." required>
                            </div>

                            <div class="mb-3 text-start">
                                <label class="fw-bold mb-1 text-secondary">LOT</label>
                                <input type="text" id="pic-lot"
                                    class="form-control form-control-sm fw-bold text-uppercase"
                                    placeholder="Contoh : A01-A10" required>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" id="btn-save-pic-personel"
                                    class="btn btn-sm btn-primary flex-grow-1 fw-bold text-uppercase py-2 rounded-pill shadow-sm">
                                    <i data-lucide="save" class="me-1"
                                        style="width: 14px; height: 14px; vertical-align: middle;"></i> Simpan Personel
                                </button>
                                <button type="button" id="btn-cancel-edit-pic"
                                    class="btn btn-sm btn-secondary fw-bold text-uppercase py-2 rounded-pill shadow-sm d-none"
                                    onclick="window.clearMasterPicForm()">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm d-flex flex-column h-100"
                    style="overflow: hidden;">
                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 flex-shrink-0 border-bottom pb-2">

                        <ul class="nav nav-pills gap-2" id="tab-master-pic-team" role="tablist">
                            <li class="nav-item">
                                <button class="btn btn-sm btn-dark fw-bold text-uppercase py-1 px-3" id="tab-btn-stock-kru"
                                    type="button" onclick="window.switchMasterPicTab('STOCK')">
                                    📦 Tim Penghitung Stock
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="btn btn-sm btn-outline-dark fw-bold text-uppercase py-1 px-3"
                                    id="tab-btn-auditor-kru" type="button" onclick="window.switchMasterPicTab('AUDITOR')">
                                    🔍 Tim Auditor / Validator
                                </button>
                            </li>
                        </ul>

                        <div class="d-flex align-items-center gap-2">
                            <select id="filter-view-pic-wh" class="form-select form-select-sm fw-bold border-primary"
                                style="width: 200px;" onchange="window.renderMasterPicTableHtml()">
                                <option value="">🌍 SEMUA GUDANG</option>
                                <option value="APW">APW</option>
                                <option value="BPW" selected>BPW</option>
                                <option value="DPW">DPW</option>
                                <option value="RPW">RPW</option>
                                <option value="DCK">DCK</option>
                            </select>
                            {{-- Input Search Baru --}}
                            <div class="input-group input-group-sm shadow-sm" style="width: 250px;">
                                <span class="input-group-text bg-light border-primary text-primary fw-bold">
                                    <i data-lucide="search" style="width: 14px; height: 14px;"></i>
                                </span>
                                <input type="text" id="search-pic-global" class="form-control fw-bold border-primary"
                                    placeholder="Cari Penneng / Nama / Lot..."
                                    onkeyup="window.renderMasterPicTableHtml()">
                            </div>
                        </div>
                    </div>

                    <div class="flex-grow-1 position-relative" style="overflow: hidden;">
                        <div class="table-responsive h-100" style="overflow-y: auto; max-height: calc(100vh - 250px);">
                            <table class="table table-sm table-hover align-middle mb-0" id="table-master-pic-list">
                                <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                                    <tr>
                                        <th width="5%" class="text-center bg-light">No.</th>
                                        <th width="12%" class="text-center bg-light">Warehouse</th>
                                        <th width="15%" class="text-center bg-light">No. Penneng / Id</th>
                                        <th class="bg-light">Nama Personel</th>
                                        <th width="15%" class="bg-light">Gedung</th>
                                        <th width="15%" class="bg-light">LOT</th>
                                        <th width="12%" class="text-center bg-light">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-master-pic-rows">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Memuat sinkronisasi
                                            database
                                            personil area...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>


        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/appkso/master_pic.js') }}"></script>
    @endpush
@endsection
