<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap KSO</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        @media print {

            .no-print,
            .running-container,
            nav {
                display: none !important;
            }

            tfoot {
                display: none;
            }

            .last-page table tfoot {
                display: table-row;
            }

            table {
                width: 100%;
                page-break-before: always;
                page-break-inside: avoid;
            }
        }

        @keyframes scrollText {
            0% {
                transform: translateX(300%);
            }

            100% {
                transform: translateX(-100%);
            }
        }
    </style>
</head>

{{-- ============================================================ --}}
{{-- HEADER ORANGE                                                --}}
{{-- ============================================================ --}}
<div
    style="background-color: #fe6807; color: #fff; width: 100%; padding: 12px 24px;
            box-sizing: border-box; display: flex; justify-content: space-between;
            align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">

    <h2 class="fw-bold mb-0" style="font-size: 20px; white-space: nowrap;">Detail Rekap KSO</h2>

    <form action="" method="GET" class="d-flex gap-2 align-items-center flex-grow-1 justify-content-end"
        id="picForm" style="max-width: 75%;">

        {{-- ── TANGGAL SO (edit tanpa reload) ── --}}
        {{-- ── TANGGAL SO ── --}}
        <div class="d-flex align-items-center gap-1" style="white-space: nowrap;">
            <label style="font-size: 11px; font-weight: bold; margin: 0;">TGL SO :</label>
            <input type="date" id="tgl-so-input" value="{{ session('tgl_so') ?? '' }}"
                style="border:none; border-radius:4px; padding:3px 6px; font-size:11px; width:130px;"
                onchange="saveTglSo(this.value)">
        </div>

        {{-- ── TANGGAL POSISI STOCK ── --}}
        <div class="d-flex align-items-center gap-1" style="white-space: nowrap;">
            <label style="font-size: 11px; font-weight: bold; margin: 0;">TGL POSISI :</label>
            <input type="date" id="tgl-posisi-input" value="{{ session('tgl_posisi_stock') ?? '' }}"
                style="border:none; border-radius:4px; padding:3px 6px; font-size:11px; width:130px;"
                onchange="saveTglPosisi(this.value)">
        </div>
        <select name="pic_name" id="picName" class="form-select form-select-sm fw-bold" style="width: 200px;"
            required>
            <option value="">Pilih PIC</option>
            @foreach (collect($pics)->sortBy(fn($pic) => $pic_map[$pic]->oprname ?? $pic) as $pic)
                <option value="{{ $pic }}" {{ $selectedPIC == $pic ? 'selected' : '' }}>
                    {{ $pic_map[$pic]->oprname ?? '-' }} - ({{ $pic }})
                </option>
            @endforeach
        </select>

        <div class="d-flex gap-2 ms-1">
            @if ($rows && $rows->count() > 0)
                <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm text-nowrap" id="btn-print-now">
                    <i data-lucide="printer" class="me-1" style="width:12px;height:12px;"></i>
                    Print Rekap KSO
                </button>
            @endif

            <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm text-nowrap" onclick="window.close();">
                <i data-lucide="arrow-left" class="me-1" style="width:12px;height:12px;"></i>
                Tutup
            </button>
        </div>


</div>


{{-- ── PILIH PIC ── --}}

{{-- ── TOMBOL ACTION ── --}}

</form>
</div>

<body>
    <div class="container position-relative" id="main-content" style="top: -13px;">

        {{-- Filter & dropdown (no-print) --}}
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap no-print">
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock') }}">Monitoring Stock</a></li>
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock/data-compare') }}">Dashboard Stock
                                Opname</a></li>
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock/tag-kso') }}">Kartu Stock
                                Opname</a></li>
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock/rekap-kso') }}">Rekap Stock
                                Opname</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="no-print my-2 border-bottom border-2"></div>

        {{-- ============================================================ --}}
        {{-- TABLE                                                        --}}
        {{-- ============================================================ --}}
        @if (!empty($rows) && $rows->count() > 0)
            <div class="mt-3 tag-info">

                <div class="mb-3">
                    <table style="width: 99%; border-collapse: collapse; border: none;">
                        <tr>
                            <td colspan="3"
                                style="padding:5px; text-align:left; border:none; font-size:20px !important;">
                                <h4>REKAP KARTU STOCK OPNAME</h4>
                            </td>
                            <td style="width:50px; padding:5px; border:none;"></td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black; border-top:1px solid black;">
                            </td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black; border-top:1px solid black;">
                            </td>
                        </tr>

                        {{-- Baris 1: TGL STOCK OPNAME --}}
                        <tr>
                            <td style="width:320px; padding:5px; border:none;"><strong>TGL STOCK OPNAME</strong></td>
                            <td style="width:1px; padding:5px; border:none; text-align:center;"><strong>:</strong></td>
                            <td style="width:200px; padding:5px; border:none;">
                                {{-- Hanya tampil teks, edit sudah di header --}}
                                <strong id="tgl-so-konten">
                                    {{ session('tgl_so') ? \Carbon\Carbon::parse(session('tgl_so'))->format('d-m-Y') : '-' }}
                                </strong>
                            </td>
                            <td style="width:50px; padding:5px; border:none;"></td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black;">
                            </td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black;">
                            </td>
                        </tr>

                        {{-- Baris 2: TGL POSISI STOCK --}}
                        <tr>
                            <td style="width:120px; padding:5px; line-height:0.5; border:none;"><strong>TGL POSISI
                                    STOCK</strong></td>
                            <td style="width:1px; padding:5px; border:none; text-align:center;"><strong>:</strong></td>
                            <td style="width:200px; padding:5px; border:none;">
                                <strong id="tgl-posisi-stock">
                                    {{ session('tgl_posisi_stock') ? \Carbon\Carbon::parse(session('tgl_posisi_stock'))->format('d-m-Y') : '-' }}
                                </strong>
                            </td>
                            <td style="width:50px; padding:5px; border:none;"></td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black;
                                       border-bottom:1px solid black;">
                                {{ $rows->first()->oprname ?? ($pic_map[$selectedPIC]->oprname ?? 'Semua PIC') }}
                            </td>
                            <td
                                style="width:200px; padding:5px; text-align:center;
                                       border-left:1px solid black; border-right:1px solid black;
                                       border-bottom:1px solid black;">
                                @php
                                    $uniqueAuditors = $rows
                                        ->pluck('auditor_nama')
                                        ->filter(fn($a) => $a && $a !== '-')
                                        ->unique()
                                        ->values();
                                @endphp
                                {{ $uniqueAuditors->isNotEmpty() ? $uniqueAuditors->implode(', ') : '-' }}
                            </td>
                        </tr>

                        {{-- Baris 3: JUMLAH KARTU STOCK --}}
                        <tr>
                            <td style="width:120px; padding:5px; line-height:0.5; border:none;"><strong>JUMLAH KARTU
                                    STOCK</strong></td>
                            <td style="width:1px; padding:5px; border:none; text-align:center;"><strong>:</strong></td>
                            <td style="width:200px; padding:5px; border:none;">
                                <strong>{{ $rows->count() }} Lembar</strong>
                            </td>
                            <td style="width:50px; padding:5px; border:none;"></td>
                            <td style="width:200px; padding:5px; text-align:center; border:1px solid black;">
                                <strong>Team Gud. Ban</strong>
                            </td>
                            <td style="width:200px; padding:5px; text-align:center; border:1px solid black;">
                                <strong>Team SO / Audit</strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="table table-bordered auto-width-table">
                    <thead>
                        <tr style="border:1px solid black; text-align:center;">
                            <th style="border:1px solid black; width:5%;">No</th>
                            <th style="border:1px solid black; width:10%;">No. Document</th>
                            <th style="border:1px solid black; width:15%;">Item Code</th>
                            <th style="border:1px solid black; width:40%;">Description</th>
                            <th style="border:1px solid black; width:15%;">Qty</th>
                            <th style="border:1px solid black; width:15%;">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalQty = 0; @endphp
                        @foreach ($rows as $index => $row)
                            @php $totalQty += $row->QtyStk ?? 0; @endphp
                            <tr style="border:1px solid black;">
                                <td style="border:1px solid black; text-align:center;">{{ $index + 1 }}</td>
                                <td style="border:1px solid black; text-align:center;">{{ $row->nokso ?? '-' }}</td>
                                <td style="border:1px solid black; text-align:center;">{{ $row->item ?? '-' }}</td>
                                <td style="border:1px solid black; text-align:left; padding-left:10px;">
                                    {{ $row->deskripsi ?? '-' }}</td>
                                <td style="border:1px solid black; text-align:right; padding-right:10px;">
                                    {{ number_format($row->QtyStk ?? 0) }}</td>
                                <td style="border:1px solid black; text-align:left;"></td>
                            </tr>
                        @endforeach
                        <tr style="border:1px solid black; font-weight:bold; background-color:#f0f0f0;">
                            <td colspan="4" style="border:1px solid black; text-align:center;">TOTAL</td>
                            <td style="border:1px solid black; text-align:center;">{{ number_format($totalQty) }}</td>
                            <td style="border:1px solid black;"></td>
                        </tr>
                    </tbody>
                </table>

            </div>
        @else
            <div style="text-align:center; padding:80px 0; color:#aaa;">
                <div style="font-size:60px; margin-bottom:16px;">📋</div>
                <div style="font-size:20px; font-weight:bold; color:#fe6807;">Silakan Pilih PIC Terlebih Dahulu</div>
                <div style="font-size:14px; margin-top:8px;">Data rekap KSO akan muncul setelah PIC dipilih dari
                    dropdown di atas.</div>
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- JAVASCRIPT                                                   --}}
    {{-- ============================================================ --}}
    <script>
        // ── PIC & AUDITOR SUBMIT ──
        const picSelect = document.getElementById('picName');
        const auditorSelect = document.getElementById('auditorSelect');
        const picForm = document.getElementById('picForm');

        picSelect?.addEventListener('change', function() {
            if (this.value !== "") {
                if (auditorSelect) auditorSelect.value = "";
                picForm.submit();
            }
        });

        auditorSelect?.addEventListener('change', function() {
            if (this.value !== "") picForm.submit();
        });



        function saveTglSo(val) {
            if (!val) return;
            fetch("{{ route('save.session.date') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tgl_so: val
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const [y, m, d] = val.split('-');
                        const elSo = document.getElementById('tgl-so-konten');
                        if (elSo) elSo.textContent = `${d}-${m}-${y}`;
                    }
                })
                .catch(() => alert('Gagal simpan TGL SO.'));
        }

        // ── SAVE TGL POSISI STOCK ──
        function saveTglPosisi(val) {
            if (!val) return;
            fetch("{{ route('save.session.date.posisi') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        tgl_posisi_stock: val
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const [y, m, d] = val.split('-');
                        const elPosisi = document.getElementById('tgl-posisi-stock');
                        if (elPosisi) elPosisi.textContent = `${d}-${m}-${y}`;
                    }
                })
                .catch(() => alert('Gagal simpan TGL Posisi.'));
        }

        // ── PRINT ──
        const btnPrint = document.getElementById('btn-print-now');
        if (btnPrint) {
            btnPrint.addEventListener('click', function() {
                const tables = Array.from(document.querySelectorAll('.tag-info table'));
                const preview = window.open('', '_blank', 'width=1200,height=800');

                const pages = [];
                for (let i = 0; i < tables.length; i += 4) {
                    pages.push(tables.slice(i, i + 4));
                }

                const pagesHTML = pages.map(pageTables => `
                    <div class="print-page">
                        ${pageTables.map(t => `<div class="form-card">${t.outerHTML}</div>`).join('')}
                    </div>
                `).join('');

                preview.document.write(`
                    <html>
                    <head>
                        <title>Print Preview</title>
                        <style>
                            @page { size: A4 portrait; margin: 10mm 5mm 5mm 5mm; }
                            body { margin: 0; padding: 0; font-family: Arial, sans-serif; }
                            .print-page {
                                display: grid;
                                grid-template-columns: repeat(1, 1fr);
                                grid-auto-rows: 40mm;
                                box-sizing: border-box;
                                page-break-after: always;
                            }
                            table { width: 100%; border-collapse: collapse; font-size: 12px; }
                            tfoot { display: none; }
                            .last-page table tfoot { display: table-row; width: 100%; }
                            .form-card { padding: 1mm; box-sizing: border-box; }
                        </style>
                    </head>
                    <body>${pagesHTML}</body>
                    </html>
                `);

                preview.document.close();
                preview.document.fonts.ready.then(() => {
                    setTimeout(() => {
                        preview.focus();
                        preview.print();
                    }, 100);
                });
            });
        }

        // ── BLOK CTRL+P ──
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                alert("Gunakan tombol Print Preview pada halaman WEB.\n\nAdiSaputra");
            }
        });
    </script>
</body>

</html>
