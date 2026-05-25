    <style>
        .running-container {
            width: 100%;
            overflow: hidden;
            height: 30px;
            position: relative;
            /* top: -20px; */
            background: #ffffff;
            border-radius: 0px;
            border: 1px solid #ffffff;
            display: flex;
            align-items: center;
            padding: 0 10px;
        }

        .running-text {
            display: inline-block;
            white-space: nowrap;
            color: #d35400;
            font-size: 16px;
            font-weight: bold;
            animation: scrollText 10s linear infinite;
        }

        /* scroll dari kanan → kiri, sesuai panjang teks */
        @keyframes scrollText {
            0% {
                transform: translateX(300%);
            }

            /* mulai dari kanan luar container */
            100% {
                transform: translateX(-100%);
            }
        }
    </style>


    <!-- Modal Print Tag KSO -->
    <div class="modal fade" id="modalPrintTagKSO" tabindex="-1" aria-labelledby="modalPrintTagKSOLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Header Modal -->
                <div class="modal-header bg-orange text-white">
                    <h5 class="modal-title" id="modalPrintTagKSOLabel">
                        <i class="bi bi-printer"></i> Print Kartu Stock Opname
                    </h5>
                </div>
                <!-- Body Modal -->
                <div class="modal-body">
                    <div class="running-container" style="top:-15px;">
                        <div class="running-text fw-bold">
                            Gunakan Browser MICROSOFT EDGE untuk Proses Print TAG STOCK
                        </div>
                    </div>

                    <!-- TAB NAVIGATION -->
                    <!-- TAB CONTENT -->
                    <div class="tab-content">
                        <!-- TAB 1: FILTER DATA -->
                        <div class="tab-pane fade show active" id="tabFilter" role="tabpanel">
                            <div class="row g-3">
                                <!-- PIC Selection -->
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-person-circle"></i> Pilih PIC (Person In Charge)
                                    </label>
                                    <select id="modalPicName" class="form-select" required>
                                        <option value="">-- Pilih PIC --</option>
                                        @foreach ($activities->unique('opr') as $act)
                                            <option value="{{ $act->opr }}">
                                                {{ $act->opr }} - {{ $act->oprname }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">PIC bertanggung jawab atas dokumen</small>
                                </div>

                                <!-- Doc From -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-file-earmark-text"></i> No. Dokumen Awal
                                    </label>
                                    <select id="modalDocFrom" class="form-select" required>
                                        <option value="">-- Pilih No. Doc Awal --</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Dokumen mulai dari nomor ini</small>
                                </div>

                                <!-- Doc To -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-file-earmark-text"></i> No. Dokumen Akhir
                                    </label>
                                    <select id="modalDocTo" class="form-select" required>
                                        <option value="">-- Pilih No. Doc Akhir --</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Dokumen sampai ke nomor ini</small>
                                </div>

                                <!-- Info Box -->
                                <div class="col-md-12">
                                    <div class="alert alert-info" id="filterInfoBox" style="display:none;">
                                        <small>
                                            <strong>Total Lembar:</strong> <span id="totalCardsInfo">0</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm" data-bs-dismiss="modal">
                        <i data-lucide="x-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                        Tutup
                    </button>
                    <button id="btnPrintNow" class="btn btn-xs btn-dark fw-bold shadow-sm">
                        <i data-lucide="printer" class="me-1" style="width: 12px; height: 12px;"></i>
                        Proses
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-orange {
            background-color: #fe6807 !important;
        }

        .text-orange {
            color: #fe6807 !important;
        }

        .btn-outline-orange {
            color: #fe6807 !important;
            border-color: #fe6807 !important;
        }

        .btn-outline-orange:hover,
        .btn-outline-orange.active,
        .btn-check:checked+.btn-outline-orange {
            background-color: #fe6807 !important;
            color: white !important;
        }

        .nav-link.active {
            border-bottom: 3px solid #fe6807 !important;
            color: #fe6807 !important;
            font-weight: bold;
        }
    </style>
