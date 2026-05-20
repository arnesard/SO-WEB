<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap KSO</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    {{-- STYLE --}}
    <style>
        @media print {

            .no-print,
            .running-container,
            nav,
            {
            display: none !important;
        }

        /* Sembunyikan 'TOTAL' di semua halaman */
        tfoot {
            display: none;
        }

        /* Hanya tampilkan TOTAL di halaman terakhir */
        .last-page table tfoot {
            display: table-row;
        }

        /* Pastikan tabel memiliki tata letak yang sesuai di halaman print */
        table {
            width: 100%;
            page-break-before: always;
            page-break-inside: avoid;
        }
        }


        /* Container running text */
        .running-container {
            width: 100%;
            height: 28px;
            overflow: hidden;
            position: relative;
            background: #ffebcc;
            border: 1px solid #ffc107;
            display: flex;
            align-items: center;
            padding: 0 10px;
        }

        .running-text {
            white-space: nowrap;
            color: #d35400;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            position: relative;
            animation: scrollText 10s linear infinite;
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

<body>
    {{-- RUNNING TEXT --}}
    <div class="running-container no-print" style="top: -20px;">
        <div class="running-text">
            Gunakan Browser MICROSOFT EDGE untuk Proses Print TAG STOCK
        </div>
    </div>

    <div class="container position-relative" id="main-content" style="top: -13px;">

        {{-- FILTER & ACTION --}}
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap no-print">
            <div class="w-75" style="height: 38px;">
                <form action="" method="GET" class="d-flex gap-2 align-items-center" id="picForm">
                    <select name="pic_name" id="picName" class="form-select w-25" required>
                        <option value="">Pilih PIC</option>
                        @foreach ($pics as $pic)
                            @php
                                $val = is_object($pic)
                                    ? $pic->value ?? ($pic->opr ?? '')
                                    : (is_array($pic)
                                        ? $pic['value'] ?? ($pic['opr'] ?? '')
                                        : $pic);
                            @endphp

                            <option value="{{ $val }}" {{ $selectedPIC == $val ? 'selected' : '' }}>
                                {{ $val }}
                            </option>
                        @endforeach
                    </select>
                    <select name="auditor" class="form-select w-25" id="auditorSelect" required>
                        <option value="">Pilih Auditor</option>
                        @foreach ($auditor as $ad)
                            <option value="{{ $ad }}"
                                {{ isset($selectedAuditor) && $selectedAuditor == $ad ? 'selected' : '' }}>
                                {{ $ad }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Tombol Print Rekap di sebelah kanan dropdown -->
                    @if ($rows && $rows->count() > 0)
                        <button type="button" class="btn btn-warning no-print" id="btn-print-now">
                            PRINT REKAP
                        </button>
                    @endif

                </form>
            </div>


            <div class="d-flex gap-2">


                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Menu
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock') }}">Monitoring Stock</a></li>
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock/data-compare') }}">Dashboard Stock
                                Opname</a></li>
                        <li><a class="dropdown-item" href="{{ url('/monitoring-stock/tag-kso') }}">Kartu Stock
                                Opname</a></li>
                        <li>
                            <a class="dropdown-item" href="{{ url('/monitoring-stock/rekap-kso') }}">
                                Rekap Stock Opname
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="no-print my-2 border-bottom border-2"></div>


        {{-- TABLE --}}
        @if (!empty($rows) && $rows->count() > 0)
            <div class="mt-3 tag-info">

                <div class="mb-3">
                    <table style="width: 99%; border-collapse: collapse; border: none;">
                        <tr>
                            <td colspan="3"
                                style="padding: 5px; text-align: left; border: none; font-size:20px !important;">
                                <h4>REKAP KARTU STOCK OPNAME</h4>
                            </td>
                            <td style="width: 50px; padding: 5px; line-height: 1; border: none;"></td>
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-top: 1px solid black;">
                            </td>
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-top: 1px solid black;">
                            </td>
                        </tr>

                        <tr>
                            <!-- Kolom 1.1 -->
                            <td style="width: 320px; padding: 5px; line-height: 1; border: none;">
                                <strong>TGL STOCK OPNAME</strong>
                            </td>
                            <!-- Kolom 1.2 -->
                            <td style="width: 1px; padding: 5px; line-height: 1; border: none; text-align: center;">
                                <strong>:</strong>
                            </td>
                            <!-- Kolom 1.3 -->
                            <td style="width: 200px; padding: 5px; line-height: 1; border: none;">
                                @if (session('tgl_so'))
                                    <div id="display-mode" style="cursor: pointer;" onclick="showEditForm()">
                                        <strong>{{ \Carbon\Carbon::parse(session('tgl_so'))->format('d-m-Y') }}</strong>
                                        <small style="color: blue; font-size: 10px;"></small>
                                    </div>

                                    <form id="edit-form" action="{{ route('save.session.date') }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        <input type="date" name="tgl_so" value="{{ session('tgl_so') }}"
                                            style="border: none" required>

                                        <button type="submit" style="border:none;" class="bg-light">✅</button>
                                        <button type="button" onclick="showTextMode()" style="border:none;"
                                            class="bg-light">❌</button>
                                    </form>
                                @else
                                    <form action="{{ route('save.session.date') }}" method="POST">
                                        @csrf
                                        <input type="date" name="tgl_so" style="border: none" required>
                                        <button type="submit" style="border:none;" class="bg-light">✅</button>
                                    </form>
                                @endif
                            </td>

                            <!-- Kolom 1.4 -->
                            <td style="width: 50px; padding: 5px; line-height: 1; border: none;"></td>
                            <!-- Kolom 1.5 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-top: none;">
                            </td>
                            <!-- Kolom 1.6 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-top: none;">
                            </td>
                        </tr>

                        <tr>
                            <!-- Kolom 2.1 -->
                            <td style="width: 120px; padding: 5px; line-height: 0.5; border: none;">
                                <strong>TGL POSISI STOCK</strong>
                            </td>
                            <!-- Kolom 2.2 -->
                            <td style="width: 1px; padding: 5px; line-height: 1; border: none; text-align: center;">
                                <strong>:</strong>
                            </td>
                            <!-- Kolom 2.3 -->
                            <td style="width: 200px; padding: 5px; line-height: 1; border: none;">
                                <div id="display-mode" style="cursor: pointer;" onclick="showEditForm()">
                                    <strong>{{ \Carbon\Carbon::parse(session('tgl_so'))->subDay(2)->format('d-m-Y') }}</strong>
                                    <small style="color: blue; font-size: 10px;"></small>
                                </div>
                            </td>

                            <!-- Kolom 2.4 -->
                            <td style="width: 50px; padding: 5px; line-height: 1; border: none;"></td>
                            <!-- Kolom 2.5 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; border-top: none;">
                                {{ $penghitung->oprname ?? 'Semua PIC' }}
                            </td>

                            <!-- Kolom 2.6 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border-left: 1px solid black; border-right: 1px solid black; border-bottom: 1px solid black; border-top: none;">
                                @if ($selectedAuditor)
                                    {{ $selectedAuditor }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <!-- Kolom 3.1 -->
                            <td style="width: 120px; padding: 5px; line-height: 0.5; border: none;">
                                <strong>JUMLAH KARTU STOCK</strong>
                            </td>
                            <!-- Kolom 3.2 -->
                            <td style="width: 1px; padding: 5px; line-height: 1; border: none; text-align: center;">
                                <strong>:</strong>
                            </td>
                            <!-- Kolom 3.3 -->
                            <td style="width: 200px; padding: 5px; line-height: 1; border: none;">
                                <strong>{{ $rows->count() }} Lembar</strong>
                            </td>

                            <!-- Kolom 3.4 -->
                            <td style="width: 50px; padding: 5px; line-height: 1; border: none;"></td>
                            <!-- Kolom 3.5 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border: 1px solid black;">
                                <strong>Team Gud. Ban</strong>
                            </td>
                            <!-- Kolom 3.6 -->
                            <td
                                style="width: 200px; padding: 5px; line-height: 1; text-align: center; border: 1px solid black;">
                                <strong>Team SO / Audit</strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="table table-bordered auto-width-table">
                    <thead>
                        <tr style="border: 1px solid black; text-align: center;">
                            <th style="border: 1px solid black; width: 5%;">No</th>
                            <th style="border: 1px solid black; width: 10%;">No. Document</th>
                            <th style="border: 1px solid black; width: 15%;">Item Code</th>
                            <th style="border: 1px solid black; width: 40%;">Description</th>
                            <th style="border: 1px solid black; width: 15%;">Qty</th>
                            <th style="border: 1px solid black; width: 15%;">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalQty = 0; @endphp
                        @foreach ($rows as $index => $row)
                            @php $totalQty += $row->qty ?? 0; @endphp
                            <tr style="border: 1px solid black;">
                                <td style="border: 1px solid black; text-align: center;">{{ $index + 1 }}</td>
                                <td style="border: 1px solid black; text-align: center;">{{ $row->nokso ?? '-' }}</td>
                                <td style="border: 1px solid black; text-align: center;">{{ $row->item ?? '-' }}</td>
                                <td style="border: 1px solid black; text-align: left; padding-left: 10px;">
                                    {{ $row->deskripsi ?? '-' }}
                                </td>
                                <td style="border: 1px solid black; text-align: right; padding-right: 10px;">
                                    {{ number_format($row->qty ?? 0) }}</td>
                                <td style="border: 1px solid black; text-align: left;"></td>
                            </tr>
                        @endforeach
                        <tr style="border: 1px solid black; font-weight: bold; background-color: #f0f0f0;">
                            <!-- TOTAL berada di kolom pertama sampai keempat -->
                            <td colspan="4" style="border: 1px solid black; text-align: center;">TOTAL</td>
                            <!-- Nilai total berada di kolom Qty -->
                            <td style="border: 1px solid black; text-align: center;">
                                {{ number_format($totalQty) }}
                            </td>
                            <!-- Kosongkan kolom Ket -->
                            <td style="border: 1px solid black;"></td>
                        </tr>
                    </tbody>


                </table>

            </div>
        @endif

    </div>

    {{-- JAVASCRIPT --}}
    <script>
        const picSelect = document.getElementById('picSelect');
        const auditorSelect = document.getElementById('auditorSelect');
        const picForm = document.getElementById('picForm');

        // Submit saat ganti PIC
        picSelect?.addEventListener('change', function() {
            if (this.value !== "") {
                // Kosongkan auditor kalau PIC ganti, biar filter lokasi kereset
                if (auditorSelect) auditorSelect.value = "";
                picForm.submit();
            }
        });

        // Submit saat ganti Auditor
        auditorSelect?.addEventListener('change', function() {
            if (this.value !== "") {
                picForm.submit();
            }
        });

        // PRINT BIASA
        document.getElementById('btn-print-now').addEventListener('click', function() {
            const tables = Array.from(document.querySelectorAll('.tag-info table'));
            const preview = window.open('', '_blank', 'width=1200,height=800');

            // Bagi tables menjadi grup 4 per halaman
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
                        @font-face {
                            font-family: 'Libre Barcode 39';
                            src: url('/fonts/LibreBarcode39-Regular.ttf') format('truetype');
                            /* font-display: swap; */
                        }

                        @page {
                            size: A4 portrait;
                            margin-top: 10mm;
                            margin-right: 5mm;
                            margin-left: 5mm;
                            margin-bottom: 5mm; /* opsional, bisa diubah atau dihapus */
                        }


                        body {
                            margin-top: 0px;
                            padding: 0;
                            font-family: Arial, sans-serif;
                        }

                        .print-page {
                            display: grid;
                            grid-template-columns: repeat(1, 1fr); /* dua kolom → kiri dan kanan */
                            grid-auto-rows: 40mm; /* tinggi setiap baris card */
                            padding: auto; /* jarak dari tepi halaman */
                            box-sizing: border-box;
                            page-break-after: always;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size:12px;
                        }

                        .digit {
                            display: inline-block;
                            width: 14px;
                            height: 14px;
                            line-height: 14px;
                            text-align: center;
                            border-radius: 50%;
                            border: 2px solid #000;
                            font-size: 10px;
                        }

                        .digit.active {
                            background: #000;
                            color: #000;
                        }

                        tfoot {
                            display: none; /* Sembunyikan TOTAL di semua halaman */
                        }

                        .last-page table tfoot {
                            display: table-row; /* Hanya tampilkan TOTAL di halaman terakhir */
                            width: 100%
                        }

                        .form-card {
                            padding: 1mm;
                            box-sizing: border-box;
                        }

                    </style>
                </head>
                <body>
                    ${pagesHTML}
                </body>
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

        // Blok Ctrl+P
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                alert(
                    "Metode Fitur print Seperti Ini tidak diizinkan,\n" +
                    "Gunakan Tombol Print Preview Pada Halaman WEB.\n" + "\n" +
                    "AdiSaputra"
                );
            }
        });


        function showEditForm() {
            // Sembunyikan teks <strong>
            document.getElementById('display-mode').style.display = 'none';
            // Munculkan form input
            document.getElementById('edit-form').style.display = 'block';
        }

        function showTextMode() {
            // Sembunyikan form input
            document.getElementById('edit-form').style.display = 'none';
            // Munculkan kembali teks <strong>
            document.getElementById('display-mode').style.display = 'block';
        }

        window.onload = function() {
            window.print();
        };
    </script>


</body>

</html>
