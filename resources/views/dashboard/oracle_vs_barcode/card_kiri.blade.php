<style>
    /* CSS Grid Kalender tetap sama seperti sebelumnya */
    .col-1-7 {
        flex: 0 0 14.285714%;
        max-width: 14.285714%;
    }

    #calendarGrid .btn-cal {
        padding: 0;
        font-size: 10px;
        font-weight: 700;
        height: 32px;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        /* Hapus border-radius bulat, ganti jadi kotak soft */
        border-radius: 4px !important;
        transition: 0.2s;
        border: none;
        /* Pastikan background transparan */
        background-color: transparent !important;
        color: #444;
        position: relative;
        cursor: pointer;
    }

    .dot-container {
        display: flex;
        gap: 3px;
        height: 5px;
        margin-top: 1px;
        justify-content: center;
    }

    .dot-oracle {
        width: 5px;
        height: 5px;
        background-color: #C8A96E;
        border-radius: 50%;
    }

    .dot-barcode {
        width: 5px;
        height: 5px;
        background-color: #132541;
        border-radius: 50%;
    }

    #calendarGrid .btn-cal.active {
        background-color: #4e4fb1 !important;
        color: #fff !important;
    }

    #calendarGrid .btn-cal.active .dot-oracle {
        background-color: #C8A96E;
    }

    #calendarGrid .btn-cal.active .dot-barcode {
        background-color: #132541 !important;
    }

    /* --- SLICER HORIZONTAL SYSTEM --- */
    .slicer-wrapper {
        display: flex;
        gap: 1px;
        overflow-x: auto;
        /* Bisa scroll samping kalau layar sempit */
        padding-bottom: 2px;
        justify-content: center;
    }

    .slicer-column {
        flex: 0 0 auto;
        /* Membagi rata lebar kolom */
        min-width: 60px;
        /* Biar gak kekecilan banget */
    }

    .slicer-header {
        font-size: 10px;
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        border-bottom: 2px solid #191bdf;
        padding-bottom: 3px;
        margin-bottom: 6px;
        white-space: nowrap;
        text-align: center;
    }

    .slicer-list {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .slicer-item {
        cursor: pointer;
        font-size: 8.5px;
        font-weight: 700;
        padding: 4px 6px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        background-color: #fff;
        color: #64748b;
        transition: 0.2s;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .slicer-item:hover {
        border-color: #191bdf;
        background-color: #eff6ff;
        color: #191bdf;
    }

    .slicer-item.active {
        background-color: #4e4fb1 !important;
        border-color: #4e4fb1 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(25, 27, 223, 0.2);
    }

    /* Custom Scrollbar Slicer */
    .slicer-wrapper::-webkit-scrollbar {
        height: 4px;
    }

    .slicer-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>

<div class="col-md-3 d-flex flex-column h-100" style="min-height: 0;">
    <div class="card border shadow-sm h-100 d-flex flex-column" style="border-radius: 10px; overflow: hidden;">
        <div class="card-header bg-light py-2 text-center" style="flex-shrink: 0;">
            <h6 class="mb-0 small fw-bold text-uppercase" style="font-size: 11px; color: #191bdf;">Control Panel</h6>
        </div>

        <div class="card-body p-2 d-flex flex-column h-100" style="overflow: hidden;">

            <div class="mt-1 mb-2 px-1 d-flex justify-content-center align-items-center gap-2" style="flex-shrink: 0;">
                <i data-lucide="filter" class="text-primary" style="width: 12px; height: 12px;"></i>
                <span style="font-size: 10px; font-weight: 900; color: #132541; text-transform: uppercase;">
                    Filter Pattern Grade OE & OK
                </span>
            </div>

            <div class="flex-grow-1 mb-0 border-bottom pb-2" style="min-height: 0;">
                <div class="row h-100 g-2">

                    <div class="col-6 d-flex flex-column border-end" style="height: 240px;">
                        <div class="slicer-header mb-1 shadow-sm text-center py-1"
                            style="color: #191bdf; font-size: 9px; border-radius: 4px; font-weight: 800; flex-shrink: 0;">
                            Grade OE
                        </div>
                        <div class="slicer-list overflow-auto custom-scroll px-1 flex-grow-1" id="slicer-oe">
                            <div class="slicer-item active mb-1 py-2 text-center shadow-sm"
                                style="font-size: 9px; font-weight: 700; min-height: 20px; display: flex; align-items: center;"
                                onclick="toggleSlicer(this, 'patterns', 'ALL_OE')">
                                ALL Grade OE</div>
                            @if (isset($allPatterns))
                                @foreach ($allPatterns as $p)
                                    @if (str_contains(strtoupper($p), 'OE'))
                                        <div class="slicer-item mb-1 py-2 px-2 text-truncate shadow-sm"
                                            style="font-size: 9px; font-weight: 700; min-height: 20px; display: flex; align-items: center;"
                                            onclick="toggleSlicer(this, 'patterns', '{{ $p }}')">
                                            <span class="text-truncate">{{ $p }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="col-6 d-flex flex-column" style="height: 240px;">
                        <div class="slicer-header mb-1 shadow-sm text-center py-1"
                            style="color: #fe6807; font-size: 9px; border-radius: 4px; font-weight: 800; flex-shrink: 0;">
                            Grade OE
                        </div>
                        <div class="slicer-list overflow-auto custom-scroll px-1 flex-grow-1" id="slicer-ok">
                            <div class="slicer-item active mb-1 py-2 text-center shadow-sm"
                                style="font-size: 9px; font-weight: 700; min-height: 20px; display: flex; align-items: center;"
                                onclick="toggleSlicer(this, 'patterns', 'ALL_OK')">
                                ALL Grade OK</div>
                            @if (isset($allPatterns))
                                @foreach ($allPatterns as $p)
                                    @if (str_contains(strtoupper($p), 'OK'))
                                        <div class="slicer-item mb-1 py-2 px-2 text-truncate shadow-sm"
                                            style="font-size: 9px; font-weight: 700; min-height: 20px; display: flex; align-items: center;"
                                            onclick="toggleSlicer(this, 'patterns', '{{ $p }}')">
                                            <span class="text-truncate">{{ $p }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 pt-0 pb-0" style="flex-shrink: 0;">
                <div
                    class="d-flex justify-content-between align-items-center mb-1 bg-white border rounded p-1 shadow-sm">
                    <button class="btn btn-xs py-0 px-2 btn-primary" onclick="changeMonth(-1)"><i
                            data-lucide="chevron-left" style="width: 12px;"></i></button>
                    <div id="calMonthTitle" class="fw-bold small text-uppercase"
                        style="font-size: 9px; color: #191bdf;"></div>
                    <button class="btn btn-xs py-0 px-2 btn-primary" onclick="changeMonth(1)"><i
                            data-lucide="chevron-right" style="width: 12px;"></i></button>
                </div>

                <div class="row g-0 text-center pb-1" style="font-size: 8px; font-weight: 900;">
                    <div class="col">S</div>
                    <div class="col">S</div>
                    <div class="col">R</div>
                    <div class="col">K</div>
                    <div class="col">J</div>
                    <div class="col text-danger">S</div>
                    <div class="col text-danger">M</div>
                </div>

                <div id="calendarGrid" class="row g-0"></div>
                <div class="d-flex justify-content-center align-items-center gap-0 mt-0 pt-0">
                    <div class="d-flex align-items-center me-1">
                        <i data-lucide="info" class="me-1 text-muted" style="width: 10px; height: 10px;"></i>
                        <span style="font-size: 9px; font-weight: 800; color: #444; letter-spacing: 0.5px;">DATA
                            :</span>
                    </div>

                    <div class="d-flex align-items-center gap-1 bg-light px-2 py-1 rounded-pill border">
                        <div class="dot-barcode" style="width: 7px; height: 7px;"></div>
                        <span style="font-size: 8px; font-weight: 700; color: #666;">BARCODE</span>
                    </div>

                    <div class="d-flex align-items-center gap-1 bg-light px-2 py-1 rounded-pill border">
                        <div class="dot-oracle" style="width: 7px; height: 7px;"></div>
                        <span style="font-size: 8px; font-weight: 700; color: #666;">ORACLE</span>
                    </div>
                </div>
            </div>


        </div>

    </div>
</div>
