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
                    Monitoring Stock (Aplikasi Barcode EDP)
                </h5>

                <div style="width: 50%; text-align: right;" class="d-flex justify-content-end align-items-center gap-1">
                    @include('appkso.navbar')
                </div>
            </div>

            <div class="col-12 h-100 d-flex flex-column flex-xl-row gap-3">

                <div class="flex-shrink-0 d-flex flex-column justify-content-center"
                    style="width: 100%; xl-max-width: 380px; max-width: 400px; min-width: 320px;">
                    <div class="card border-0 shadow-sm p-4 text-center h-100 d-flex flex-column justify-content-center"
                        style="border-radius: 12px; background: #ffffff;">
                        <div class="p-3 rounded-circle bg-success bg-opacity-10 mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 65px; height: 65px;">
                            <i data-lucide="file-up" style="width: 28px; height: 28px;" class="text-success"></i>
                        </div>
                        <h6 class="fw-black text-dark mb-1 text-uppercase" style="letter-spacing: 0.5px;">Upload Barcode
                            Monitoring
                            Stock</h6>
                        <p class="text-muted mb-3" style="font-size: 11px; line-height: 14px;">Unggah berkas
                            <strong>.csv</strong>
                            hasil Export dari Aplikasi Barcode Desktop.
                        </p>

                        <form id="form-upload-barcode" enctype="multipart/form-data">
                            @csrf
                            <div class="text-start mb-3">
                                <label class="fw-bold mb-1 text-secondary" style="font-size: 11px;">Target Warehouse</label>
                                <select id="upload-target-wh" name="target_warehouse"
                                    class="form-select form-select-sm fw-bold border-success" required>
                                    <option value="APW">APW</option>
                                    <option value="BPW" selected>BPW</option>
                                    <option value="DPW">DPW</option>
                                    <option value="RPW">RPW</option>
                                </select>
                            </div>

                            <div class="border border-2 border-dashed rounded-3 p-4 bg-light position-relative transition-all"
                                style="border-color: #28a745 !important;">
                                <input type="file" id="file-csv" name="file_csv"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;"
                                    accept=".csv" required>
                                <i data-lucide="upload-cloud" class="text-muted mb-2"
                                    style="width: 24px; height: 24px;"></i>
                                <div class="small fw-bold text-secondary text-truncate px-2" id="text-file-csv"
                                    style="font-size: 11px;">Klik atau seret file CSV ke sini</div>
                            </div>
                            <button type="submit"
                                class="btn btn-sm btn-success w-100 fw-bold mt-3 py-2 rounded-pill shadow-sm text-uppercase"
                                style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Proses Import Data
                            </button>
                        </form>
                    </div>
                </div>

                <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm d-flex flex-column h-100"
                    style="overflow: hidden;">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            <select id="filter-monstock-wh" class="form-select form-select-sm fw-bold border-primary"
                                style="width: 360px;" onchange="window.filterMonstockTableLogic()">
                                <option value="" selected>⚠️ PILIH GUDANG</option>
                            </select>
                            <div id="last-upload-container" class="small d-none transition-all">
                                <span class="badge bg-light text-dark border py-2 px-3" style="font-size: 11px;">
                                    <i class="fa-solid fa-clock text-success me-1"></i>
                                    Terakhir Upload: <strong id="last-upload-text" class="text-danger">-</strong>
                                </span>
                            </div>
                        </div>

                        <div class="input-group input-group-sm" style="max-width: 420px; width: 100%;">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="search-monstock-item"
                                class="form-control form-control-sm border-start-0 border-end-0 ps-0"
                                style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()"
                                placeholder="Cari item, rack, atau location...">

                            <button class="btn btn-primary fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                                type="button" id="btn-submit-search-monstock"
                                onclick="window.filterMonstockTableLogic(true)" style="font-size: 10px;">
                                <i data-lucide="search" style="width: 12px; height: 12px;"></i> CARI
                            </button>

                            <button class="btn btn-dark fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                                type="button" id="btn-reset-search-monstock" onclick="window.resetMonstockSearchField()"
                                style="font-size: 10px;">
                                <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i> RESET
                            </button>
                            <button class="btn btn-danger fw-bold px-3 d-flex align-items-center gap-1 text-uppercase"
                                type="button" id="btn-delete-db-monstock" onclick="window.confirmDeleteDbMonstock()"
                                title="⚠️ Hanya developer yang memiliki akses. Tindakan ini akan menghapus seluruh database monstock!"
                                style="font-size: 10px;">
                                <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i> DELETE DB
                            </button>
                        </div>
                    </div>

                    <div class="flex-grow-1 position-relative" style="overflow: hidden;">
                        <div class="table-responsive h-100" style="overflow-y: auto; max-height: calc(100vh - 250px);">
                            <table class="table table-sm table-hover align-middle mb-0" id="table-barcode-monstock"
                                style="font-size: 11px;">
                                <thead class="table-light text-uppercase fw-bold position-sticky top-0"
                                    style="z-index: 5; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6;">
                                    <tr>
                                        <th class="bg-light text-center">No.</th>
                                        <th class="bg-light text-center" width="10%">Warehouse</th>
                                        <th class="bg-light" width="12%">Rack Code</th>
                                        <th class="bg-light" width="15%">Item Code</th>
                                        <th class="bg-light" width="22%">Description</th>
                                        <th class="bg-light text-end" width="10%">Jml (Pcs)</th>
                                        <th class="bg-light text-end" width="10%">OEM (Pcs)</th>
                                        <th class="bg-light text-center">Location Code</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-barcode-monstock"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/appkso/barcode_mon_stock.js') }}"></script>
    @endpush
@endsection
