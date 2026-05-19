<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tag KSO</title>

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Libre Barcode 39';
            src: url('/fonts/LibreBarcode39-Regular.ttf') format('truetype');
            /* font-display: swap; */
        }

        html,
        body {
            height: 100%;
            /* Penting: memastikan tinggi penuh */
            overflow: hidden;
            /* Penting: menghilangkan scrollbar utama halaman */
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .tag-info {
            max-height: 100vh;
            overflow-y: auto;
            padding-right: 5px;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 14px;
            text-align: center;
            vertical-align: middle;
            width: 25%;
        }

        .label {
            font-weight: bold;
            text-align: left;
            padding-left: 6px;
        }

        .value {
            text-align: left;
            padding-left: 6px;
        }

        .pic-footer {
            text-align: center;
            font-weight: bold;
            height: 30px;
        }

        .tag-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .tag-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            padding: 10px 0;
        }

        @media print {

            html,
            body {
                height: auto;
                overflow: visible;
            }

            .tag-info {
                overflow: visible;
                max-height: none;
            }

            .running-container {
                display: none;
            }
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
</head>

<body>
    <div
        style="background-color: #fe6807; color: #fff; width: 100%; padding: 16px 24px; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">

        <!-- Judul -->
        <h2 class="fw-bold mb-0" style="font-size: 30px;">Detail Tag Stok</h2>
        <!-- Total OK / OE -->
        <div class="d-flex align-items-center gap-2">
            <h5 class="mb-0">Total OK: {{ number_format($rows->sum('ok'), 0) }} Pcs</h5>
            <span class="mx-1">||</span>
            <h5 class="mb-0">Total OE: {{ number_format($rows->sum('oe'), 0) }} Pcs</h5>
        </div>
        <!-- Tombol Kembali + Print Preview -->
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"onclick="window.close();">
                <i data-lucide="x-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                Tutup
            </button>
            <button class="btn btn-xs btn-dark fw-bold shadow-sm" id="btn-preview">Print Tag Stok</button>
        </div>
    </div>

    <div class="container" id="main-content" style="position:relative; top:-13px;">
        <hr class="my-2">
        <div class="tag-info">
            @if (!empty($rows))
                @foreach ($rows as $t)
                    <table class="mb-3"
                        style="width:420px; border:2px solid #000; border-collapse:collapse; font-size:14px;">
                        <!-- Header TAG STOCK -->
                        <tr>
                            <td colspan="4"
                                style="border:1px solid #000; padding:20px; text-align:center; font-weight:bold; font-size:25px; background:#f2f2f2;">
                                TAG STOCK
                            </td>
                        </tr>
                        <!-- No. Doc -->
                        <tr>
                            <td colspan="4"
                                style="
                                border-top:1px solid #000;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:none;
                                padding:1px;
                                text-align:left;
                                text-indent:5px;
                            ">
                                No. Doc :
                            </td>
                        </tr>
                        <!-- NoDoc Value & Barcode -->
                        <tr>
                            <td colspan="2"
                                style="
                                border-top:none;
                                border-left:1px solid #000;
                                border-right:none;
                                border-bottom:1px solid #000;
                                padding:1;
                            ">
                                <div
                                    style="position:relative; top:-1px; left:15px; font-weight:bold; font-size:23px; line-height:1; text-align:left;">
                                    {{ $t['noDoc'] ?? '-' }}
                                </div>
                            </td>
                            <td colspan="2"
                                style="
                                border-top:none;
                                border-left:none;
                                border-right:1px solid #000;
                                border-bottom:1px solid #000;
                                padding:0;
                            ">
                                <div
                                    style="position:relative; top:0px; left:-20px; font-family:'Libre Barcode 39', cursive; font-size:40px; line-height:0.6; text-align:left;">
                                    *{{ $t['noDoc'] ?? '-' }}*
                                </div>
                            </td>
                        </tr>
                        <!-- Loccode -->
                        <tr>
                            <td colspan="2"
                                style="border:1px solid #000; border-bottom:none; padding:5px; text-align:center; font-weight:bold; font-size:22px;">
                                {{ isset($t['loccode']) ? substr($t['loccode'], 0, 5) : '-' }}
                            </td>
                            <td colspan="2"
                                style="border:1px solid #000; border-bottom:none; padding:5px; text-align:center; font-weight:bold; font-size:22px;">
                                {{ isset($t['loccode']) ? substr($t['loccode'], -3) : '-' }}
                            </td>
                        </tr>

                        <!-- Spacer -->
                        <tr>
                            <td colspan="4"
                                style="
                                border-top:none;
                                border-bottom:none;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                padding:0px;
                            ">
                            </td>
                        </tr>
                        <!-- Item Code -->
                        <tr>
                            <td colspan="4"
                                style="
                                border-top:1px solid #000;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:none;
                                padding:1px;
                                text-align:right;
                                position: relative;   /* memungkinkan geser teks */
                                left: -5px;          /* geser 20px ke kiri, bisa diubah sesuai kebutuhan */
                            ">
                                Item Code :
                            </td>
                        </tr>
                        <!-- Barcode & Item (kotak kecil dan naik ke atas) -->
                        <tr>
                            <td colspan="3"
                                style="
                                border-left:1px solid #000;
                                border-right:none;
                                border-top:none;
                                border-bottom:none;
                                padding:0px;
                                font-family:'Libre Barcode 39', cursive;
                                font-size:35px;
                                text-align:left;
                                vertical-align:top;
                                line-height:0.5;
                                height:-100px;
                                position: relative;
                                left: 30px;
                                top: 8px;
                            ">
                                *{{ $t['item'] ?? '-' }}*
                            </td>
                            <td colspan="1"
                                style="
                                border-left:none;
                                border-right:1px solid #000;
                                border-top:none;
                                border-bottom:none;
                                font-size:23px;
                                padding:0px;
                                text-align:right;
                                font-weight:bold;
                                line-height:1;
                                vertical-align:top;
                                white-space: nowrap;
                                width:200px;
                                position: relative; /* wajib biar left berfungsi */
                                left: -45px;        /* geser ke kiri */
                            ">
                                {{ $t['item'] ?? '-' }}
                            </td>
                        </tr>
                        <!-- Description -->
                        <tr>
                            <td colspan="4"
                                style="
                                border-top:none;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:none;
                                padding:0px;
                                text-align:right;
                                vertical-align:top;
                                font-size:20px;
                                position: relative;   /* memungkinkan geser teks */
                                left: -5px;          /* geser 20px ke kiri, bisa diubah sesuai kebutuhan */
                            ">
                                {{ $t['description'] ?? ($t['item'] ?? '-') }}
                            </td>
                        </tr>
                        <!-- Jumlah Rak & Pcs -->
                        <tr>
                            <td colspan="1"
                                style="
                                border-top:1px solid #000;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:none;
                                padding:0px;
                                text-indent:5px;
                                text-align:left;
                            ">
                                Jumlah Rak:
                            </td>

                            <td colspan="3"
                                style="
                                border-top:1px solid #000;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:none;
                                padding:0px;
                                text-indent:5px;
                                text-align:left;
                            ">
                                Jumlah Pcs:
                            </td>
                        </tr>
                        <!-- Rackcode, Total Pcs & Barcode -->
                        <tr>
                            <td colspan="1"
                                style="
                                border-top:none;
                                border-left:1px solid #000;
                                border-right:1px solid #000;
                                border-bottom:1px solid #000;
                                padding:0px;
                                text-align:center;
                                font-size:30px;
                                position:relative;
                                top:-2px;
                                line-height:1;
                                font-weight:bold;
                            ">
                                {{ $t['rackcode'] ?? 0 }}
                            </td>
                            <td colspan="1"
                                style="
                                border-top:none;
                                border-left:1px solid #000;
                                border-right:none;
                                border-bottom:1px solid #000;
                                padding:0px;
                                text-align:right;
                                font-size:30px;
                                position:relative;
                                top:-2px;
                                left:20px;
                                line-height:1;
                                font-weight:bold;
                            ">
                                {{ number_format(($t['oe'] ?? 0) + ($t['ok'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td colspan="2"
                                style="
                                border-top:none;
                                border-left:none;
                                border-right:1px solid #000;
                                border-bottom:1px solid #000;
                                padding:0px;
                                font-family:'Libre Barcode 39', cursive;
                                font-size:40px;
                                text-align:center;
                                position:relative;
                                top:6px;
                                line-height:0.8;
                            ">
                                *{{ ($t['oe'] ?? 0) + ($t['ok'] ?? 0) }}*
                            </td>
                        </tr>
                        <!-- Rincian & PIC -->
                        <tr>
                            <td colspan="4"
                                style="border:1px solid #000; border-bottom:1px solid #000; padding:0px; text-align:left; text-indent:5px;">
                                Rincian :
                            </td>
                        </tr>
                        <!-- PIC Box -->
                        <tr>
                            <td colspan="1" style="border:none; padding:5px; text-align:center;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cell
                            </td>
                            <td colspan="1" style="border:none; padding:5px; text-align:center;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Susun
                            </td>
                            <td colspan="1" style="border:none; padding:5px; text-align:center;">
                                &nbsp;Isi
                            </td>
                            <td colspan="1" style="border:none; padding:5px; text-align:left;">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding:13px; text-align:center;">
                                A : __________ x __________ x __________ = ___________
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding:13px; text-align:center;">
                                B : __________ x __________ x __________ = ___________
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding:13px; text-align:center;">
                                C : __________ x __________ x __________ = ___________
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border:1px solid #000; padding:7px; text-align:center;">PIC</td>
                            <td colspan="2"
                                style="border:none; padding:0px; text-align:right;position:relative; top:10px;">
                                Grand Total&nbsp; = ___________&nbsp;&nbsp;&nbsp;&nbsp;
                            </td>

                        </tr>

                        <tr>
                            <td colspan="2" style="border:1px solid #000; padding:30px; text-align:center;"></td>
                            <td colspan="2" style="border:none;"></td>

                        </tr>
                        <!-- PIC Value -->
                        <tr>
                            <td colspan="2"
                                style="border:1px solid #000; padding:15px; text-align:center; font-weight:bold;">
                                {{ $pic ?? '-' }}
                            </td>
                            <td colspan="2" style="border:none;"></td>

                        </tr>
                    </table>
                @endforeach
            @else
                <p>Tidak ada data untuk ditampilkan, lihat kembali no. doc yang akan diproses</p>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('btn-preview').addEventListener('click', function() {

            const tables = Array.from(document.querySelectorAll('.tag-info table'));
            const preview = window.open('', '_blank', 'width=1200,height=800');

            // ambil SEMUA CSS WEB
            const styles = Array.from(
                document.querySelectorAll('link[rel="stylesheet"], style')
            ).map(el => el.outerHTML).join('');

            // pagination (4 tag / halaman)
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

                    ${styles} <!-- CSS WEB ASLI -->

                    <style>
                        /* HANYA KHUSUS PRINT */
                        @page {
                            size: A4 portrait;
                            margin: 10mm 3mm 3mm 3mm;
                        }

                        html, body {
                            height: auto !important;
                            overflow: visible !important;
                        }

                        .running-container {
                            display: none !important;
                        }

                        .print-page {
                            display: grid;
                            grid-template-columns: repeat(2, 1fr);
                            grid-auto-rows: 158mm;
                            gap: 5mm;
                            page-break-after: always;
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

        // Tangkap print dari menu browser (Edge compatible)
        window.onbeforeprint = function() {
            alert(
                "Metode Fitur print Seperti Ini tidak diizinkan,\n" +
                "Gunakan Tombol Print Preview Pada Halaman WEB.\n" + "\n" +
                "AdiSaputra"
            );

            // Solusi untuk Edge: reload halaman agar batal masuk mode print
            setTimeout(() => {
                location.reload();
            }, 10);
        };
    </script>
</body>

</html>
