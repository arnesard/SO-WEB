@php
    $currentRoute = Route::currentRouteName();
    $listKso = $listKso ?? ($list_kso ?? collect());
    $selectedSo = $selectedSo ?? ($selected_so ?? '');
@endphp

<div class="d-flex align-items-center justify-content-end gap-1 flex-nowrap w-100 ms-auto">

    <a href="{{ route('appkso.master_item.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.master_item.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="package" class="me-1" style="width: 12px; height: 12px;"></i>
        1. Master Item
    </a>

    <a href="{{ route('appkso.master_pic.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.master_pic.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="users" class="me-1" style="width: 12px; height: 12px;"></i>
        2. Master PIC
    </a>

    <a href="{{ route('appkso.barcode_mon_stock.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.barcode_mon_stock.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="barcode" class="me-1" style="width: 12px; height: 12px;"></i>
        3. Barcode Monitoring Stock
    </a>

    <a href="{{ route('appkso.tag_stock.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.tag_stock.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="tag" class="me-1" style="width: 12px; height: 12px;"></i>
        4. Tag Stock
    </a>

    <a href="{{ route('appkso.non_barcode.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.non_barcode.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="clipboard-check" class="me-1" style="width: 12px; height: 12px;"></i>
        5. Non Barcode
    </a>

    <a href="{{ route('appkso.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="clipboard-check" class="me-1" style="width: 12px; height: 12px;"></i>
        6. APPKSO
    </a>

    <a href="{{ route('appkso.snapshot.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'appkso.snapshot.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="database" class="me-1" style="width: 12px; height: 12px;"></i>
        7. SNAPSHOT
    </a>

    <a href="{{ route('dashboard_so_auto.index') }}"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'dashboard_so_auto.index' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;">
        <i data-lucide="layout-dashboard" class="me-1" style="width: 12px; height: 12px;"></i>
        8. DASHBOARD
    </a>

    <button class="btn btn-xs btn-dark fw-bold shadow-sm ms-1" style="font-size: 10px;" type="button"
        onclick="openExportBAModal()">
        <i data-lucide="file-check-2" class="me-1" style="width: 12px; height: 12px;"></i>
        9. Export BA
    </button>

    {{-- <button type="button"
        class="btn btn-xs fw-bold shadow-sm text-nowrap {{ $currentRoute === 'live.progress' ? 'btn-warning text-dark' : 'btn-dark' }}"
        style="font-size: 10px;" onclick="openNavbarProgressModal()">
        <i data-lucide="activity" class="me-1" style="width: 12px; height: 12px;"></i>
        9. LIVE PROGRESS
    </button> --}}

</div>

<script>
    function openNavbarProgressModal() {
        let inputOptions = {};
        @forelse ($listKso as $kso)
            inputOptions["{{ $kso->so_name }}"] = "{{ $kso->so_name }}";
        @empty
        @endforelse

        if (Object.keys(inputOptions).length === 0) {
            window.location.href = '/auto-progress';
            return;
        }

        Swal.fire({
            title: 'Pilih SO Aktif',
            html: '<div style="font-size: 12px; color: #555; margin-bottom: 8px;">Pilih Stock Opname yang ingin dipantau:</div>',
            input: 'select',
            inputOptions: inputOptions,
            inputValue: "{{ $selectedSo }}",
            showCancelButton: true,
            confirmButtonText: 'Buka Live Progress',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#fe6807',
            cancelButtonColor: '#6c757d',
            inputAttributes: {
                style: 'font-size: 13px; padding: 6px; border-radius: 6px;'
            },
            preConfirm: (val) => {
                if (!val) {
                    Swal.showValidationMessage('Pilih salah satu SO dulu bro!');
                    return false;
                }
                return val;
            }
        }).then((result) => {
            if (result.value) {
                window.location.href = '/auto-progress?so_name=' + encodeURIComponent(result.value.trim());
            }
        });
    }

    // FUNGSI UNTUK EXPORT BA
    function openExportBAModal() {
        let soOptions = `<option value="">-- Pilih Stock Opname --</option>`;
        @foreach ($listKso as $kso)
            soOptions +=
                `<option value="{{ $kso->so_name }}" ${"{{ $selectedSo }}" === "{{ $kso->so_name }}" ? "selected" : ""}>{{ $kso->so_name }}</option>`;
        @endforeach

        if (soOptions === `<option value="">-- Pilih Stock Opname --</option>`) {
            Swal.fire({
                title: 'Data Kosong!',
                text: 'Belum ada Stock Opname yang aktif bro!',
                type: 'warning'
            });
            return;
        }

        Swal.fire({
            title: 'Setup Berita Acara',
            html: `
                <div class="text-start mt-3">
                    <label style="font-size: 11px; font-weight: bold; color: #555;">Pilih Stock Opname</label>
                    <select id="swal-so" class="form-select mb-3" style="font-size: 13px;">
                        ${soOptions}
                    </select>

                    <label style="font-size: 11px; font-weight: bold; color: #555;">Tanggal Stock Opname</label>
                    <input id="swal-tgl-so" type="date" class="form-control mb-3" style="font-size: 13px;">

                    <label style="font-size: 11px; font-weight: bold; color: #555;">Tanggal Cut Off Stock</label>
                    <input id="swal-tgl-cutoff" type="date" class="form-control" style="font-size: 13px;">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i data-lucide="download" class="me-1"></i> Generate Excel',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            preConfirm: () => {
                const so_name = document.getElementById('swal-so').value;
                const tgl_so = document.getElementById('swal-tgl-so').value;
                const tgl_cutoff = document.getElementById('swal-tgl-cutoff').value;

                if (!so_name || !tgl_so || !tgl_cutoff) {
                    Swal.showValidationMessage('Semua form wajib diisi komandan!');
                    return false;
                }
                return {
                    so_name,
                    tgl_so,
                    tgl_cutoff
                };
            },
            onOpen: () => {
                if (window.lucide) lucide.createIcons();
            }
        }).then((result) => {
            if (result.value) {
                let params = result.value;
                // Redirect bawa parameter
                window.location.href =
                    `/appkso?so_name=${encodeURIComponent(params.so_name)}&auto_export_ba=1&tgl_so=${params.tgl_so}&tgl_cutoff=${params.tgl_cutoff}`;
            }
        });
    }
</script>
