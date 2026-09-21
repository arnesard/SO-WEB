@extends('layouts.app')

@section('content')
    <style>
        .table-container {
            max-height: 55vh;
            overflow-y: auto;
        }

        /* Sticky Header */
        .table-container thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
            box-shadow: inset 0 -1px 0 #dee2e6;
        }

        /* Sticky Footer */
        .table-container tfoot th,
        .table-container tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background-color: #f8f9fa;
            box-shadow: inset 0 1px 0 #dee2e6;
        }

        .summary-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: scale(1.02);
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div
                class="card-header bg-white border-bottom py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center">
                    <!-- Icon ditaruh di luar div teks agar sejajar di sebelah kiri -->
                    <i data-lucide="file-text" class="me-2 text-dark" style="width: 20px; height: 20px;"></i>

                    <!-- Div ini mengatur teks agar menumpuk ke bawah (baris 1 dan baris 2) -->
                    <div class="d-flex flex-column align-items-start">
                        <h5 class="mb-0 fw-bold text-dark">
                            Report SO Karantina
                        </h5>
                        <small class="text-muted fw-semibold" style="font-size: 11px;">
                            *Klik Card Untuk melihat detail Data
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">

                    <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" onclick="loadData()">
                        <i data-lucide="refresh-cw" size="16"></i> Refresh
                    </button>
                    <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                        onclick="openPrintRekapModal()">
                        <i data-lucide="printer" size="16"></i> Excel  Rekap SO karantina
                    </button>
                    <button type="button" class="btn btn-sm btn-warning d-flex align-items-center gap-1"
                        data-bs-toggle="modal" data-bs-target="#modalUploadBstb">
                        <i data-lucide="upload" size="16"></i> Upload BSTB Barcode
                    </button>
                    <button class="btn btn-sm btn-success d-flex align-items-center gap-1" onclick="exportExcel()">
                        <i data-lucide="file-spreadsheet" size="16"></i> Export Form Karantina & Rekap BSTB
                    </button>
                    <a href="{{ route('so_karantina.menu') }}"
                        class="btn btn-sm btn-info text-white d-flex align-items-center gap-1 fw-bold">
                        <i data-lucide="smartphone" size="16"></i> Akses Menu Handheld
                    </a>
                </div>
            </div>

            <!-- Area Summary Cards -->
            <div class="row mb-3 g-2">
                <div class="col-md-1">
                    <div class="card shadow-sm border-0 summary-card h-100" style="border-left-color: #0d6efd !important;">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-top text-primary-emphasis fw-bold text-center lh-sm"
                            style="font-size: 0.75rem;">
                            Variance
                            <span class="d-block">Grade OE</span>
                        </div>
                        <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center">
                            <h4 class="mb-0 fw-bold text-dark" id="card-var-oe-qty">0</h4>
                            <small class="text-secondary mt-1" style="font-size: 0.7rem;">
                                <span id="card-var-oe-sku">0</span> SKU
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-1">
                    <div class="card shadow-sm border-0 summary-card h-100" style="border-left-color: #198754 !important;">
                        <div class="bg-success bg-opacity-10 p-2 rounded-top text-success-emphasis fw-bold text-center lh-sm"
                            style="font-size: 0.75rem;">
                            Variance
                            <span class="d-block">Grade OK</span>
                        </div>
                        <div class="card-body p-2 d-flex flex-column justify-content-center align-items-center">
                            <h4 class="mb-0 fw-bold text-dark" id="card-var-ok-qty">0</h4>
                            <small class="text-secondary mt-1" style="font-size: 0.7rem;">
                                <span id="card-var-ok-sku">0</span> SKU
                            </small>
                        </div>
                    </div>
                </div>
                <!-- Card OE -->
                <div class="col-md-1">
                    <div class="card shadow-sm border-0 summary-card h-100" style="border-left-color: #0d6efd !important;">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-top text-primary-emphasis fw-bold text-center"
                            style="font-size: 0.75rem;">
                            Total Produksi Grade OE
                        </div>

                        <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                            <h4 class="mb-1 fw-bold text-dark" id="card-oe-qty">0</h4>
                            <small class="text-secondary" style="font-size: 0.7rem;">
                                <span id="card-oe-sku">0</span> SKU
                            </small>
                        </div>
                    </div>
                </div>
                <!-- Card OK -->
                <div class="col-md-1">
                    <div class="card shadow-sm border-0 summary-card h-100" style="border-left-color: #198754 !important;">
                        <div class="bg-success bg-opacity-10 p-2 rounded-top text-primary-emphasis fw-bold text-center"
                            style="font-size: 0.75rem;">
                            Total Produksi Grade OK
                        </div>

                        <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                            <h4 class="mb-1 fw-bold text-dark" id="card-ok-qty">0</h4>
                            <small class="text-secondary" style="font-size: 0.7rem;">
                                <span id="card-ok-sku">0</span> SKU
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card shadow-sm border-0 summary-card h-100"
                        style="border-left-color: #ffc107 !important; cursor: pointer;" onclick="openPlantModal('B')">
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">*Produksi Plant B</h6>
                                    <h4 class="mb-0 fw-bold text-dark" id="card-plant-b-qty">0</h4>
                                    <small class="text-secondary" style="font-size: 0.7rem;"><span
                                            id="card-plant-b-sku">0</span> SKU</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-2 rounded">
                                    <i data-lucide="factory" class="text-warning" size="20"></i>
                                </div>
                            </div>
                            <div id="card-plant-b-grades" class="mt-auto border-top pt-2"
                                style="font-size: 0.65rem; max-height: 60px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card shadow-sm border-0 summary-card h-100"
                        style="border-left-color: #ffc107 !important; cursor: pointer;" onclick="openPlantModal('H')">
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">*Produksi Plant H</h6>
                                    <h4 class="mb-0 fw-bold text-dark" id="card-plant-h-qty">0</h4>
                                    <small class="text-secondary" style="font-size: 0.7rem;"><span
                                            id="card-plant-h-sku">0</span> SKU</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-2 rounded">
                                    <i data-lucide="factory" class="text-warning" size="20"></i>
                                </div>
                            </div>
                            <div id="card-plant-h-grades" class="mt-auto border-top pt-2"
                                style="font-size: 0.65rem; max-height: 60px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card shadow-sm border-0 summary-card h-100"
                        style="border-left-color: #ffc107 !important; cursor: pointer;" onclick="openPlantModal('I')">
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">*Produksi Plant I</h6>
                                    <h4 class="mb-0 fw-bold text-dark" id="card-plant-i-qty">0</h4>
                                    <small class="text-secondary" style="font-size: 0.7rem;"><span
                                            id="card-plant-i-sku">0</span> SKU</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-2 rounded">
                                    <i data-lucide="factory" class="text-warning" size="20"></i>
                                </div>
                            </div>
                            <div id="card-plant-i-grades" class="mt-auto border-top pt-2"
                                style="font-size: 0.65rem; max-height: 60px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="card shadow-sm border-0 summary-card h-100"
                        style="border-left-color: #ffc107 !important; cursor: pointer;" onclick="openPlantModal('T')">
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem;">*Produksi Plant T</h6>
                                    <h4 class="mb-0 fw-bold text-dark" id="card-plant-t-qty">0</h4>
                                    <small class="text-secondary" style="font-size: 0.7rem;"><span
                                            id="card-plant-t-sku">0</span> SKU</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-2 rounded">
                                    <i data-lucide="factory" class="text-warning" size="20"></i>
                                </div>
                            </div>
                            <div id="card-plant-t-grades" class="mt-auto border-top pt-2"
                                style="font-size: 0.65rem; max-height: 60px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-1 border-bottom d-flex flex-wrap align-items-center justify-content-end gap-2">

                        <div style="min-width: 160px;">
                            <select id="filter-grade" class="form-select form-select-sm" onchange="loadData()">
                                <option value="">-- Semua Grade --</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade }}">{{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="min-width: 160px;">
                            <select id="filter-keterangan" class="form-select form-select-sm" onchange="loadData()">
                                <option value="">-- Semua Keterangan --</option>
                                <option value="Sesuai">Sesuai</option>
                                <option value="Tidak Sesuai">Tidak Sesuai</option>
                            </select>
                        </div>

                        <div style="min-width: 250px;">
                            <input type="text" id="search-item" class="form-control form-select-sm"
                                placeholder="Cari Item Code / Description...">
                        </div>

                        <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" onclick="loadData()">
                            <i data-lucide="search" size="16"></i> Cari
                        </button>

                    </div>
                    <div class="card-body p-1">
                        <div class="table-responsive table-container border">
                            <table class="table table-hover text-center align-middle mb-0" id="table-so-karantina">
                                <thead class="text-secondary">
                                    <tr>
                                        <th width="5%">NO</th>
                                        <th>ITEM CODE</th>
                                        <th>ITEM DESCRIPTION</th>
                                        <th width="8%">Shift 1</th>
                                        <th width="8%">Shift 2</th>
                                        <th width="8%">Shift 3</th>
                                        <th width="8%" class="bg-light">TOTAL</th>
                                        <th width="10%" class="bg-light text-primary">Hasil Scan</th>
                                        <th width="8%">Variance</th>
                                        <th width="10%">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-so-karantina">
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">Memuat data...</td>
                                    </tr>
                                </tbody>
                                <tfoot class="fw-bold text-dark">
                                    <tr>
                                        <td colspan="3" class="text-end">GRAND TOTAL (<span id="tfoot-sku">0</span>
                                            SKU)
                                        </td>
                                        <td id="tfoot-shift1">0</td>
                                        <td id="tfoot-shift2">0</td>
                                        <td id="tfoot-shift3">0</td>
                                        <td id="tfoot-total" class="bg-light">0</td>
                                        <td id="tfoot-scan" class="bg-light text-primary">0</td>
                                        <td id="tfoot-variance">0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Upload BSTB Barcode -->
        <div class="modal fade" id="modalUploadBstb" tabindex="-1" aria-labelledby="modalUploadBstbLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('so_karantina.upload_bstb.upload') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="modalUploadBstbLabel">
                                <i data-lucide="upload" class="me-2" size="18"></i>
                                Upload BSTB Barcode
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Upload file Excel (.xlsx, .xls, .csv) hasil export BSTB. Data lama akan otomatis
                                <span class="fw-bold text-danger">ditimpa (truncate)</span> dengan data baru dari file ini.
                            </p>
                            <div class="mb-3">
                                <label for="file_excel" class="form-label fw-semibold">Pilih File Excel</label>
                                <input type="file" name="file_excel" id="file_excel" class="form-control"
                                    accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Maksimal ukuran file 10 MB.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i data-lucide="upload" size="14"></i> Upload Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalUploadBstb" tabindex="-1" aria-labelledby="modalUploadBstbLabel"
            aria-hidden="true">
        </div>
        <!-- Modal Card -->
        <div class="modal fade" id="modalDetailPlant" tabindex="-1" aria-labelledby="modalDetailPlantLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="modalDetailPlantLabel">
                            <i data-lucide="factory" class="me-2 text-warning" size="20"></i>
                            Detail Produksi Plant <span id="modal-plant-name" class="text-primary"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="bg-white p-3 border-bottom d-flex justify-content-around text-center">
                            <div>
                                <small class="text-muted d-block fw-bold">Total SKU</small>
                                <h5 class="mb-0 fw-bold text-dark" id="modal-plant-sku">0</h5>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">Total Shift 1</small>
                                <h5 class="mb-0 fw-bold text-dark" id="modal-plant-s1">0</h5>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">Total Shift 2</small>
                                <h5 class="mb-0 fw-bold text-dark" id="modal-plant-s2">0</h5>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">Total Shift 3</small>
                                <h5 class="mb-0 fw-bold text-dark" id="modal-plant-s3">0</h5>
                            </div>
                            <div>
                                <small class="text-muted d-block fw-bold">Grand Total</small>
                                <h5 class="mb-0 fw-bold text-primary" id="modal-plant-total">0</h5>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                            <table class="table table-hover table-bordered text-center align-middle mb-0"
                                id="table-detail-plant">
                                <thead class="table-light text-secondary" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th width="5%">NO</th>
                                        <th>ITEM CODE</th>
                                        <th>ITEM DESCRIPTION</th>
                                        <th width="10%">Shift 1</th>
                                        <th width="10%">Shift 2</th>
                                        <th width="10%">Shift 3</th>
                                        <th width="10%" class="bg-warning bg-opacity-10 text-dark">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-detail-plant">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Tabel -->
        <div class="modal fade" id="modalDetailScan" tabindex="-1" aria-labelledby="modalDetailScanLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="modalDetailScanLabel">
                            <i data-lucide="scan-line" class="me-2 text-primary" size="20"></i>
                            Detail Hasil Scan: <span id="modal-scan-item" class="text-danger"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                            <table class="table table-hover table-bordered text-center align-middle mb-0"
                                id="table-detail-scan">
                                <thead class="table-light text-secondary" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th width="5%">No.</th>
                                        <th width="10%">opr</th>
                                        <th width="15%">Nama</th>
                                        <th width="15%">NoDoc</th>
                                        <th width="20%">item_code_desc</th>
                                        <th>description</th>
                                        <th width="10%" class="bg-primary bg-opacity-10 text-dark">QtyStk</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-detail-scan">
                                </tbody>
                                <tfoot class="fw-bold text-dark">
                                    <tr>
                                        <td colspan="6" class="text-end">TOTAL QTY:</td>
                                        <td id="tfoot-scan-detail-qty" class="bg-primary bg-opacity-10 text-primary">0
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
<!-- Modal Print Rekap -->
<div class="modal fade" id="modalPrintRekap" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header py-2 px-3" style="background-color: #dc3545; color: white;">
                <h6 class="modal-title mb-0 fw-bold d-flex align-items-center gap-2" style="font-size: 12px;">
                    <i data-lucide="file-spreadsheet" style="width: 14px; height: 14px;"></i>
                    Export Rekap SO Karantina
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">

                {{-- Info ringkas --}}
                <div id="modal-rekap-info"
                    class="p-2 rounded mb-3 text-center"
                    style="background: rgba(220,53,69,0.07); border: 1px solid rgba(220,53,69,0.2); font-size: 11px;">
                    <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                    <span class="ms-2 text-muted">Memuat data operator...</span>
                </div>

                <p class="text-muted mb-3" style="font-size: 11px;">
                    Pilih jenis export rekap yang akan di-download:
                </p>

                <div class="d-grid gap-2">
                    {{-- Internal: dengan TOTAL --}}
                    <button type="button" id="btn-export-internal"
                        class="btn btn-sm fw-bold text-white disabled"
                        style="background-color: #198754; font-size: 11px; border-radius: 8px;">
                        <i data-lucide="file-spreadsheet" style="width: 13px; height: 13px;"></i>
                        Export Internal
                        <small class="d-block fw-normal opacity-75" style="font-size: 9px;">
                            Kolom TOTAL terisi angka
                        </small>
                    </button>

                    {{-- External: TOTAL kosong --}}
                    <button type="button" id="btn-export-external"
                        class="btn btn-sm fw-bold text-white disabled"
                        style="background-color: #0d6efd; font-size: 11px; border-radius: 8px;">
                        <i data-lucide="file-spreadsheet" style="width: 13px; height: 13px;"></i>
                        Export External
                        <small class="d-block fw-normal opacity-75" style="font-size: 9px;">
                            Kolom TOTAL dikosongkan
                        </small>
                    </button>
                </div>

            </div>
            <div class="modal-footer py-2 px-3" style="background: #f8f9fa;">
                <button type="button" class="btn btn-sm btn-light border fw-bold"
                    data-bs-dismiss="modal" style="font-size: 11px;">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
    @endsection

    @push('scripts')
        <script src="{{ asset('js/excel.min.js') }}"></script>
        <script src="{{ asset('js/so_karantina/so_karantina.js') }}"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if (session('success'))
                    Swal.fire({
                        title: "Upload Berhasil! 🎉",
                        text: "{{ session('success') }}",
                        icon: "success",
                        confirmButtonColor: "#198754",
                        timer: 3000
                    });
                @endif

                @if (session('error'))
                    Swal.fire({
                        title: "Upload Gagal!",
                        text: "{{ session('error') }}",
                        icon: "error",
                        confirmButtonColor: "#d33"
                    });
                @endif

                @if ($errors->any())
                    Swal.fire({
                        title: "Validasi Gagal!",
                        text: "{{ $errors->first() }}",
                        icon: "error",
                        confirmButtonColor: "#d33"
                    });
                @endif
            });
        </script>
    @endpush
