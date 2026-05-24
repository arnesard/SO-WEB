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
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            background-color: #fff;
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
            border-collapse: collapse;
        }

        .label,
        .value {
            text-align: left;
            padding-left: 6px;
        }

        .label {
            font-weight: bold;
        }

        @media print {

            .no-print,
            .running-container,
            nav {
                display: none !important;
            }

            html,
            body {
                overflow: visible !important;
                height: auto !important;
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-after: always;
                position: relative;
            }

            .barcode {
                position: absolute;
                top: 10px;
                right: 10px;
                text-align: center;
                font-weight: normal;
                font-family: 'Libre Barcode 39', 'Times New Roman', Times, serif;
                font-size: 40px;
                line-height: 1;
            }
        }

        .running-container {
            width: 100%;
            overflow: hidden;
            height: 28px;
            position: relative;
            background: #ffebcc;
            border: 1px solid #ffc107;
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
    <!-- Header Detail KSO Full-Width dengan tombol Kembali & Print -->
    <div
        style="background-color: #fe6807; color: #fff; width: 100%; padding: 16px 24px; box-sizing: border-box; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">

        <!-- Judul -->
        <h2 class="fw-bold mb-0" style="font-size: 28px;">Detail KSO</h2>

        <!-- Tombol kanan -->
        <div style="display: flex; gap: 8px; align-items: center;">
<<<<<<< HEAD
=======
            <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"
                onclick="window.location.href='/appkso/rekap-kso?pic_name={{ urlencode($selectedPIC ?? '') }}'">
                <i data-lucide="printer" class="me-1" style="width: 12px; height: 12px;"></i>
                Print Rekap KSO
            </button>

>>>>>>> bad3c010e0c3457493b1e20570ac2c6e2f61d196
            @if ($rows && $rows->count() > 0)
                <div>
                    <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm" id="btn-print-now">
                        <i data-lucide="printer" class="me-1" style="width: 12px; height: 12px;"></i>
                        PRINT KSO
                    </button>
                </div>
            @endif
            <div>
                <!-- Tombol Kembali -->
                <button type="button" class="btn btn-xs btn-dark fw-bold shadow-sm"onclick="window.close();">
                    <i data-lucide="x-circle" class="me-1" style="width: 12px; height: 12px;"></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
    <div class="container-fluid" id="main-content" style="top:-13px; position:relative;">


        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap no-print hidden">
            <div class="w-75" id="tagging_form_wrapper" style="height:38px;">
                <form action="#" method="GET" class="d-flex gap-2 mb-3 align-items-center"
                    onsubmit="return false;">
                    <!-- PIC Selection -->
                    <select name="pic_name" id="picName" class="form-select w-25" required>
                        <option value="">Pilih PIC</option>
                        @foreach ($pics as $pic)
                            <option value="{{ $pic['value'] }}" {{ $selectedPIC == $pic['value'] ? 'selected' : '' }}>
                                {{ $pic['text'] }}
                            </option>
                        @endforeach
                    </select>

                    <!-- Doc From -->
                    <select name="doc_from" id="docFrom" class="form-select w-25" required>
                        <option value="">Pilih No. Doc Awal</option>
                        @if ($selectedPIC && isset($picNoksoMap[$selectedPIC]))
                            @foreach ($picNoksoMap[$selectedPIC] as $nokso)
                                <option value="{{ $nokso }}" {{ $docFrom == $nokso ? 'selected' : '' }}>
                                    {{ $nokso }}
                                </option>
                            @endforeach
                        @endif
                    </select>

                    <!-- Doc To -->
                    <select name="doc_to" id="docTo" class="form-select w-25" required>
                        <option value="">Pilih No. Doc Akhir</option>
                        @if ($selectedPIC && isset($picNoksoMap[$selectedPIC]))
                            @foreach ($picNoksoMap[$selectedPIC] as $nokso)
                                @if (!$docFrom || $nokso >= $docFrom)
                                    <option value="{{ $nokso }}" {{ $docTo == $nokso ? 'selected' : '' }}>
                                        {{ $nokso }}
                                    </option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                </form>
            </div>
        </div>

        <!-- Tambahkan CSS hidden -->
        <style>
            .hidden {
                display: none !important;
            }
        </style>
    </div>

    <div class="no-print" style="height:4px; margin:10px 0; border-bottom:2px solid #ccc;"></div>

    <div class="tag-info">
        @if ($rows && $rows->count() > 0)
            @foreach ($rows as $t)
                <?php
                $qty_str = (string) $t->qty;
                
                $get_digit = function ($qty_str, $index_from_end) {
                    $length = strlen($qty_str);
                    $target_index = $length - $index_from_end;
                    if ($target_index >= 0) {
                        return (int) $qty_str[$target_index];
                    }
                    return null;
                };
                
                $puluhan_ribu_digit = $get_digit($qty_str, 5);
                $ribuan_digit = $get_digit($qty_str, 4);
                $ratusan_digit = $get_digit($qty_str, 3);
                $puluhan_digit = $get_digit($qty_str, 2);
                $satuan_digit = $get_digit($qty_str, 1);
                
                $get_style = function ($digit, $value) {
                    if ($digit !== null && $digit === (int) $value) {
                        return 'display:inline-block; text-align:center; height:8px; width:8px; border-radius:50%; border: 8px solid #000; color:transparent !important; box-sizing:border-box;';
                    }
                    return '';
                };
                ?>

                {{-- UNTUK ARSIP GUDANG --}}
                <div class="page-break" style="position: relative; min-height: 100vh;">
                    <table class="mb-1" style="width:100%; border-collapse:collapse; font-size:14px;">
                        <tr>
                            <td colspan="6"
                                style="border:none; text-align:center; font-weight:bold; font-size:20px; font-family: 'Times New Roman', Times, serif; position: relative;">
                                KARTU STOCK OPNAME
                                <p
                                    style="font-size:13px; margin:0; line-height:1; font-family: 'Times New Roman', Times, serif; font-weight: normal;">
                                    TANGGAL : 22 / DESEMBER / 2025</p>
                                <p
                                    style="font-size:23px; padding-top:10px; line-height:1;margin-bottom:0; font-family: 'Times New Roman', Times, serif;">
                                    {{ substr($t->item, -1) === '0' ? 'OE' : 'OK' }}</p>
                                <div class="barcode"
                                    style="position: absolute; top: 0; right: 0; text-align: center; font-weight: normal;">
                                    <div style="font-size:12px; font-family: 'Times New Roman', Times, serif;">KODE
                                        DOKUMEN & NO. DOC</div>
                                    <div style="font-size:40px; line-height:1; font-family: 'Libre Barcode 39';">
                                        *{{ $t->nokso }}*</div>
                                    <div
                                        style="font-size:12px; font-family: 'Times New Roman', Times, serif; position: relative; top: -15px;">
                                        {{ $t->nokso }}</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                PT GAJAH TUNGGAL Tbk</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; white-space: nowrap; font-family: 'Times New Roman', Times, serif;">
                                BARANG MILIK PLANT</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">:
                                    <b>B</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; padding-bottom:1px; text-align:left; vertical-align: top; text-indent:5px; font-weight:bold; font-size:18px; font-family: 'Times New Roman', Times, serif;">
                                <b>{{ $t->deskripsi }}</b>
                            </td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                NO. DOCUMENT</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">:
                                    <b>{{ $t->nokso }}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; font-size:50px; text-align:left; text-indent:5px; font-family: 'Libre Barcode 39', cursive;">
                                *{{ $t->item }}*</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; vertical-align:top; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                NO. INDEX</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif; vertical-align:middle;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:1px; position: relative; top: -20px;">:</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; font-weight:bold; padding-bottom:1px; font-size:10px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                {{ $t->item }}</td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                LINE NUMBER</td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                GRADE</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ substr($t->item, -1) === '0' ? 'OE' : 'OK' }}</b></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                PLANT : <b>B</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan_ribu"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($puluhan_ribu_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                JENIS</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                :</td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ribuan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($ribuan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($ribuan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($ribuan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($ribuan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($ribuan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($ribuan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($ribuan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($ribuan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($ribuan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($ribuan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                UKURAN</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ $t->deskripsi }}</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ratusan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($ratusan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($ratusan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($ratusan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($ratusan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($ratusan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($ratusan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($ratusan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($ratusan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($ratusan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($ratusan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                CODE</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ $t->item }}</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($puluhan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($puluhan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($puluhan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($puluhan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($puluhan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($puluhan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($puluhan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($puluhan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($puluhan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($puluhan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                JUMLAH</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif; white-space: nowrap;">
                                : <b>{{ number_format($t->qty, 0, ',', '.') }} PCS</b> <span
                                    style="font-family: 'Libre Barcode 39'; font-size:30px; line-height:1; font-weight: normal; margin-left:10px; position: relative; top: 5px;">*{{ $t->qty }}*</span>
                            </td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="satuan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($satuan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($satuan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($satuan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($satuan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($satuan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($satuan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($satuan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($satuan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($satuan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($satuan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr style="border-bottom: 2px solid black;">
                            <td colspan="4"></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                LEMBAR UNTUK GUDANG</td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                DIHITUNG OLEH</td>
                            <td colspan="2" style="padding-top:25px "></td>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                DIPERIKSA OLEH</td>
                        </tr>
                        <tr>
                            <td style="width:10%;padding-bottom:30px"></td>
                            <td style="width:10%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div
                                    style="margin:2px auto; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    <b>{{ $t->oprname }}</b>
                                </div>
                            </td>
                            <td colspan="2" style="padding-top:15px "></td>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div
                                    style="margin:1px auto; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    <b>..........</b>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                <div
                                    style="width:80%; margin:1px auto; border-top:2px solid #000; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    GUDANG BAN</div>
                            </td>
                            <td colspan="2"></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                <div
                                    style="width:60%; margin:1px auto; border-top:2px solid #000; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    TEAM S.O./AUDITOR</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" style="position: relative;"><img src="/images/garis_gunting.png"
                                    alt="Garis Gunting"
                                    style="width: 100%; height: 50px; position: relative; top: 14px;" /></td>
                        </tr>
                    </table>


                    {{-- UNTUK ARSIP JAKARTA --}}
                    <table class="mb-1" style="width:100%; border-collapse:collapse; font-size:14px;">
                        <tr>
                            <td colspan="6"
                                style="border:none;text-align:center; font-weight:bold; font-size:20px; font-family: 'Times New Roman', Times, serif;">
                                KARTU STOCK OPNAME
                                <p
                                    style="font-size:13px; margin:0; line-height:1; font-family: 'Times New Roman', Times, serif; font-weight: normal;">
                                    TANGGAL : 22 / DESEMBER / 2025</p>
                                <p
                                    style="font-size:23px; padding-top:10px; line-height:1;margin-bottom:0; font-family: 'Times New Roman', Times, serif;">
                                    {{ substr($t->item, -1) === '0' ? 'OE' : 'OK' }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                PT GAJAH TUNGGAL Tbk</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; white-space: nowrap; font-family: 'Times New Roman', Times, serif;">
                                BARANG MILIK PLANT</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">:
                                    <b>B</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; padding-bottom:1px; text-align:left; vertical-align: top; text-indent:5px; font-weight:bold; font-size:18px; font-family: 'Times New Roman', Times, serif;">
                                <b>{{ $t->deskripsi }}</b>
                            </td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                NO. DOCUMENT</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">:
                                    <b>{{ $t->nokso }}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; font-size:50px; color:transparent; text-align:left; text-indent:5px; font-family: 'Libre Barcode 39', cursive;">
                                *{{ $t->item }}*</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; vertical-align:top; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                NO. INDEX</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif; vertical-align:middle;">
                                <span
                                    style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:1px; position: relative; top: -20px;">:</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4"
                                style="border:none; font-weight:bold; padding-bottom:1px; font-size:10px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                {{ $t->item }}</td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                LINE NUMBER</td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                GRADE</td>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ substr($t->item, -1) === '0' ? 'OE' : 'OK' }}</b></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                PLANT : <b>B</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan_ribu"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($puluhan_ribu_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($puluhan_ribu_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                JENIS</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                :</td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ribuan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($ribuan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($ribuan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($ribuan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($ribuan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($ribuan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($ribuan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($ribuan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($ribuan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($ribuan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($ribuan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                UKURAN</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ $t->deskripsi }}</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ratusan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($ratusan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($ratusan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($ratusan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($ratusan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($ratusan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($ratusan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($ratusan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($ratusan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($ratusan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($ratusan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                CODE</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ $t->item }}</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($puluhan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($puluhan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($puluhan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($puluhan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($puluhan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($puluhan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($puluhan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($puluhan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($puluhan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($puluhan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                JUMLAH</td>
                            <td colspan="3"
                                style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                : <b>{{ number_format($t->qty, 0, ',', '.') }} PCS</b></td>
                            <td colspan="2"
                                style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="satuan"
                                    style="display:flex; justify-content:space-between; font-weight:bold; text-indent:5px;text-align:center;padding-right:25px">
                                    <div style="{{ $get_style($satuan_digit, 1) }}">1</div>
                                    <div style="{{ $get_style($satuan_digit, 2) }}">2</div>
                                    <div style="{{ $get_style($satuan_digit, 3) }}">3</div>
                                    <div style="{{ $get_style($satuan_digit, 4) }}">4</div>
                                    <div style="{{ $get_style($satuan_digit, 5) }}">5</div>
                                    <div style="{{ $get_style($satuan_digit, 6) }}">6</div>
                                    <div style="{{ $get_style($satuan_digit, 7) }}">7</div>
                                    <div style="{{ $get_style($satuan_digit, 8) }}">8</div>
                                    <div style="{{ $get_style($satuan_digit, 9) }}">9</div>
                                    <div style="{{ $get_style($satuan_digit, 0) }}">0</div>
                                </div>
                            </td>
                        </tr>
                        <tr style="border-bottom: 2px solid black;">
                            <td colspan="4"></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                LEMBAR UNTUK ARSIP</td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                DIHITUNG OLEH</td>
                            <td colspan="2" style="padding-top:25px "></td>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                DIPERIKSA OLEH</td>
                        </tr>
                        <tr>
                            <td style="width:10%;padding-bottom:30px"></td>
                            <td style="width:10%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                            <td style="width:20%;padding-bottom:30px"></td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div
                                    style="margin:2px auto; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    <b>{{ $t->oprname }}</b>
                                </div>
                            </td>
                            <td colspan="2" style="padding-top:15px "></td>
                            <td colspan="2"
                                style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div
                                    style="margin:1px auto; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    <b>..........</b>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                <div
                                    style="width:80%; margin:1px auto; border-top:2px solid #000; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    GUDANG BAN</div>
                            </td>
                            <td colspan="2"></td>
                            <td colspan="2"
                                style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">
                                <div
                                    style="width:60%; margin:1px auto; border-top:2px solid #000; text-align:center; font-family: 'Times New Roman', Times, serif;">
                                    TEAM S.O./AUDITOR</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        @else
            <p>Tidak ada data untuk ditampilkan, lihat kembali no. doc yang akan diproses</p>
        @endif
    </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        const picNoksoMap = @json($picNoksoMap);
        const picName = document.getElementById('picName');
        const docFrom = document.getElementById('docFrom');
        const docTo = document.getElementById('docTo');

        // Update DOC dropdown saat PIC berubah
        picName.addEventListener('change', function() {
            const selectedPIC = this.value;
            docFrom.innerHTML = '<option value="">Pilih No. Doc Awal</option>';
            docTo.innerHTML = '<option value="">Pilih No. Doc Akhir</option>';

            if (!selectedPIC || !picNoksoMap[selectedPIC]) return;

            picNoksoMap[selectedPIC].forEach(nokso => {
                docFrom.innerHTML += `<option value="${nokso}">${nokso}</option>`;
                docTo.innerHTML += `<option value="${nokso}">${nokso}</option>`;
            });
        });

        // DOC TO >= DOC FROM
        docFrom.addEventListener('change', function() {
            const selectedPIC = picName.value;
            const from = this.value;

            docTo.innerHTML = '<option value="">Pilih No. Doc Akhir</option>';

            if (!selectedPIC || !picNoksoMap[selectedPIC]) return;

            picNoksoMap[selectedPIC]
                .filter(nokso => nokso >= from)
                .forEach(nokso => {
                    docTo.innerHTML += `<option value="${nokso}">${nokso}</option>`;
                });
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

            const pagesHTML = pages.map(pageTables => {
                return `<div class="print-page">
                    ${pageTables.map(t => `<div class="form-card">${t.outerHTML}</div>`).join('')}
                </div>`;
            }).join('');

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
                            margin-bottom: 0mm; /* opsional, bisa diubah atau dihapus */
                        }


                        body {
                            margin-top: 0px;
                            padding: 0;
                            font-family: Arial, sans-serif;
                        }

                        .print-page {
                            display: grid;
                            grid-template-columns: repeat(1, 1fr); /* dua kolom → kiri dan kanan */
                            grid-auto-rows: 142mm; /* tinggi setiap baris card */
                            padding: auto; /* jarak dari tepi halaman */
                            box-sizing: border-box;
                            page-break-after: always;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            height: 100%;
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
        // Blok Ctrl+Print
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
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>
