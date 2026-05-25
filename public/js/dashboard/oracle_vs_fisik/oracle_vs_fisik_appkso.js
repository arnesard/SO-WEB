$(document).ready(function () {
    console.log(
        "🔥 APPKSO MODULE RUNNING WITH PURIFIED TABLES & INTERACTIVE DRILL-DOWN MODAL",
    );

    // Wadah penampung cache data detail utama biar gak usah nembak AJAX server bolak-balik
    window.cachedAppksoDetail = [];

    // Ambil Filter Gudang Unik dari DB
    function loadAppksoViewFilters() {
        let viewWhSelect = $("#appkso-view-warehouse");
        if (!viewWhSelect || viewWhSelect.length === 0) return;

        // Ganti URL ke route yang baru saja didaftarkan
        $.get("/oracle-fisik/appkso/get-warehouses", function (res) {
            if (res.status === "success") {
                viewWhSelect.html(
                    '<option value="" selected>⚠️ PILIH GUDANG</option>',
                );
                res.warehouses.forEach((wh) => {
                    viewWhSelect.append(
                        `<option value="${wh}">${wh.toUpperCase()}</option>`,
                    );
                });
            }
        });
    }

    $(document)
        .off("change", "#appkso-view-warehouse")
        .on("change", "#appkso-view-warehouse", function () {
            window.loadTableAppkso();
        });

    // Fungsi Render Multi-Card Dinamis
    window.loadTableAppkso = function () {
        let tbodyDetail = $("#tbody-appkso");
        let tbodyResume = $("#tbody-appkso-resume");
        let tbodyPattern = $("#tbody-appkso-pattern");
        let selectedViewWh = $("#appkso-view-warehouse").val();

        $("#search-table-global").val("");
        window.cachedAppksoDetail = []; // Bersihkan cache data lama

        // Reset teks metric header atas ke nol pas ganti gudang bro
        $(
            "#summary-pattern-sku, #summary-pattern-qty, #summary-resume-operator, #summary-resume-qty",
        ).text("0");

        if (!selectedViewWh || selectedViewWh === "") {
            tbodyDetail.html(
                `<tr><td colspan="11" class="text-center text-muted py-4 fw-bold">⚠️ Silakan pilih saringan gudang di panel filter atas untuk memuat data bro</td></tr>`,
            );
            tbodyResume.html(
                `<tr><td colspan="4" class="text-center text-muted py-4">Silakan saring target gudang di atas.</td></tr>`,
            );
            tbodyPattern.html(
                `<tr><td colspan="4" class="text-center text-muted py-4">Silakan saring target gudang di atas.</td></tr>`,
            );
            return;
        }

        tbodyDetail.html(
            '<tr><td colspan="11" class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div> Menyisir database detail APPKSO...</td></tr>',
        );
        tbodyResume.html(
            '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div> Membagi data operator...</td></tr>',
        );
        tbodyPattern.html(
            '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div> Menghitung Snapshot Pattern...</td></tr>',
        );

        $.get(
            "/oracle-fisik/appkso/data",
            { warehouse: selectedViewWh },
            function (res) {
                // Simpan respon data detail utama ke variabel window global biar bisa di-drilled down instan
                window.cachedAppksoDetail = res.detail || [];

                // 📊 1. RENDER & SORTING RESUME OPERATOR (SORT BY SKU DESCENDING)
                let htmlResume = "";
                if (!res.resume || res.resume.length === 0) {
                    htmlResume = `<tr><td colspan="4" class="text-center text-muted py-4 fw-bold">Tidak ada ringkasan resume di warehouse ${selectedViewWh}</td></tr>`;
                } else {
                    res.resume.sort(
                        (a, b) =>
                            parseInt(b.total_sku || 0) -
                            parseInt(a.total_sku || 0),
                    );

                    let totalOperatorAktif = res.resume.length;
                    let sumResumeQty = 0;

                    res.resume.forEach((row, idx) => {
                        let totalSku = row.total_sku
                            ? parseInt(row.total_sku)
                            : 0;
                        let totalQty = row.total_qty
                            ? parseInt(row.total_qty)
                            : 0;
                        let fullOprName =
                            (row.oprname ?? "-") +
                            " (" +
                            (row.opr ?? "-") +
                            ")";

                        sumResumeQty += totalQty;

                        htmlResume += `
                            <tr class="click-drilldown-resume" data-opr="${row.opr}" data-oprname="${row.oprname}" style="cursor: pointer;" title="Klik untuk melirik detail data operator ${row.oprname}">
                                <td class="text-center font-monospace text-muted fw-bold">${idx + 1}</td>
                                <td class="text-start fw-bold text-dark text-truncate" style="max-width: 190px;">
                                    <i data-lucide="user" style="width: 11px; height: 11px; vertical-align: middle;" class="text-muted me-1"></i>${fullOprName}
                                </td>
                                <td class="font-monospace fw-bold text-danger">${totalSku.toLocaleString("id-ID")} SKU</td>
                                <td class="text-end font-monospace fw-bold text-primary">${totalQty.toLocaleString("id-ID")} PCS</td>
                            </tr>
                        `;
                    });

                    $("#summary-resume-operator").text(
                        totalOperatorAktif.toLocaleString("id-ID") + " ORG",
                    );
                    $("#summary-resume-qty").text(
                        sumResumeQty.toLocaleString("id-ID"),
                    );
                }
                tbodyResume.html(htmlResume);

                // 📈 2. RENDER & SORTING SNAPSHOT PATTERN BAN (SORT BY QTY DESCENDING)
                let htmlPattern = "";
                if (!res.pattern || res.pattern.length === 0) {
                    htmlPattern = `<tr><td colspan="4" class="text-center text-muted py-4 fw-bold">Tidak ada ringkasan data pattern di warehouse ${selectedViewWh}</td></tr>`;
                } else {
                    res.pattern.sort(
                        (a, b) =>
                            parseInt(b.total_qty || 0) -
                            parseInt(a.total_qty || 0),
                    );

                    let sumPatternSku = 0;
                    let sumPatternQty = 0;

                    res.pattern.forEach((row, idx) => {
                        let totalSku = row.total_sku
                            ? parseInt(row.total_sku)
                            : 0;
                        let totalQty = row.total_qty
                            ? parseInt(row.total_qty)
                            : 0;

                        sumPatternSku += totalSku;
                        sumPatternQty += totalQty;

                        htmlPattern += `
                            <tr class="click-drilldown-pattern" data-pattern="${row.pattern_name}" style="cursor: pointer;" title="Klik untuk melirik detail ban ukuran ${row.pattern_name}">
                                <td class="text-center font-monospace text-muted fw-bold">${idx + 1}</td>
                                <td class="text-start fw-bold text-dark font-monospace text-truncate" style="max-width: 190px;">
                                    <i data-lucide="layers" style="width: 11px; height: 11px; vertical-align: middle;" class="text-muted me-1"></i>${row.pattern_name ?? "-"}
                                </td>
                                <td class="font-monospace fw-bold text-secondary">${totalSku.toLocaleString("id-ID")} SKU</td>
                                <td class="text-end font-monospace fw-bold text-danger">${totalQty.toLocaleString("id-ID")} PCS</td>
                            </tr>
                        `;
                    });

                    $("#summary-pattern-sku").text(
                        sumPatternSku.toLocaleString("id-ID"),
                    );
                    $("#summary-pattern-qty").text(
                        sumPatternQty.toLocaleString("id-ID"),
                    );
                }
                tbodyPattern.html(htmlPattern);

                // 📋 3. RENDER DATA TABEL DETAIL MASTER BARIS APPKSO
                let htmlDetail = "";
                if (!res.detail || res.detail.length === 0) {
                    htmlDetail = `<tr><td colspan="11" class="text-center text-muted py-4 fw-bold">Tidak ada rekaman data APPKSO di warehouse ${selectedViewWh}</td></tr>`;
                } else {
                    res.detail.forEach((row, i) => {
                        htmlDetail += `
                        <tr class="appkso-data-row"
                            data-opr="${String(row.opr).toLowerCase()}"
                            data-oprname="${String(row.oprname).toLowerCase()}"
                            data-nokso="${String(row.nokso).toLowerCase()}"
                            data-item="${String(row.item).toLowerCase()}">
                            <td class="text-center font-monospace text-muted">${i + 1}</td>
                            <td class="fw-bold text-secondary text-center">${row.warehouse ?? "-"}</td>
                            <td class="font-monospace text-center">${row.tgl ?? "-"}</td>
                            <td class="font-monospace text-center fw-bold text-secondary">${row.opr ?? "-"}</td>
                            <td class="fw-bold text-dark">${row.oprname ?? "-"}</td>
                            <td class="font-monospace text-center fw-bold text-success">${row.nokso ?? "-"}</td>
                            <td class="font-monospace fw-bold text-danger">${row.item ?? "-"}</td>
                            <td class="text-muted fw-bold">${row.deskripsi ?? "-"}</td>
                            <td class="text-end fw-bold font-monospace text-primary">${Number(row.qty).toLocaleString("id-ID")}</td>
                            <td class="fw-bold text-dark">${row.verifikasi_nama ?? "-"}</td>
                            <td class="font-monospace text-muted text-center">${row.tanggal_verifikasi ?? "-"}</td>
                        </tr>
                    `;
                    });
                }
                tbodyDetail.html(htmlDetail);

                if (typeof lucide !== "undefined") {
                    lucide.createIcons();
                }
            },
        );
    };

    // ==========================================
    // 🛠️ LOGIKA INTERAKSI DRILL-DOWN POP-UP MODAL (6 KOLOM)
    // ==========================================
    function renderDrilldownModalRows(filteredData, titleText, titleIcon) {
        let tbodyModal = $("#tbody-modal-drilldown");
        let htmlModal = "";

        $("#modal-drilldown-title").html(
            `<i data-lucide="${titleIcon}" style="width: 14px; height: 14px; vertical-align: middle;"></i> ${titleText}`,
        );

        let uniqueItemsCount = new Set(filteredData.map((row) => row.item))
            .size;
        $("#modal-total-rows").text(uniqueItemsCount);

        if (filteredData.length === 0) {
            htmlModal = `<tr><td colspan="6" class="text-center text-muted py-4 fw-bold">Tidak ada rincian data detail yang cocok bro.</td></tr>`;
        } else {
            filteredData.forEach((row, i) => {
                let qtyFisik = row.qty ? parseInt(row.qty) : 0;

                htmlModal += `
                    <tr>
                        <td class="text-center font-monospace text-muted fw-bold">${i + 1}</td>
                        <td class="font-monospace text-secondary fw-bold">${row.opr ?? "-"}</td>
                        <td class="text-dark fw-bold text-start text-truncate" style="max-width: 150px;" title="${row.oprname ?? "-"}">${row.oprname ?? "-"}</td>
                        <td class="font-monospace fw-bold text-danger">${row.item ?? "-"}</td>
                        <td class="text-muted fw-bold text-start text-uppercase text-truncate" style="max-width: 250px;" title="${row.deskripsi ?? "-"}">${row.deskripsi ?? "-"}</td>
                        <td class="text-end font-monospace fw-bold text-primary">${qtyFisik.toLocaleString("id-ID")} PCS</td>
                    </tr>
                `;
            });
        }
        tbodyModal.html(htmlModal);

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }

        let myModal = new bootstrap.Modal(
            document.getElementById("modal-appkso-drilldown"),
        );
        myModal.show();
    }

    $(document).on("click", ".click-drilldown-resume", function () {
        let selectedOprCode = String($(this).attr("data-opr")).trim();
        let selectedOprName = $(this).attr("data-oprname");
        let matchedRows = window.cachedAppksoDetail.filter(
            (row) => String(row.opr).trim() === selectedOprCode,
        );
        let title = `DAFTAR BARANG OPNAME: ${selectedOprName.toUpperCase()} (${selectedOprCode})`;
        renderDrilldownModalRows(matchedRows, title, "user");
    });

    $(document).on("click", ".click-drilldown-pattern", function () {
        let targetPattern = String($(this).attr("data-pattern")).trim();
        let matchedRows = [];

        if (targetPattern.toLowerCase() === "kosong / unmapped") {
            matchedRows = window.cachedAppksoDetail.filter((row) => {
                return (
                    !row.pattern_name ||
                    row.pattern_name === "" ||
                    String(row.pattern_name).toLowerCase() ===
                    "kosong / unmapped"
                );
            });
        } else {
            let searchStr = targetPattern.toLowerCase();
            matchedRows = window.cachedAppksoDetail.filter((row) => {
                let currentPattern = row.pattern_name
                    ? String(row.pattern_name).toLowerCase()
                    : "";
                let desc = row.deskripsi
                    ? String(row.deskripsi).toLowerCase()
                    : "";
                let codeItem = row.item ? String(row.item).toLowerCase() : "";
                return (
                    currentPattern === searchStr ||
                    desc.includes(searchStr) ||
                    codeItem.includes(searchStr)
                );
            });
        }
        let title = `RINCIAN DAFTAR SKU SIZE BAN: ${targetPattern.toUpperCase()}`;
        renderDrilldownModalRows(matchedRows, title, "boxes");
    });

    // ==========================================
    // 🎯 1. PEMICU UTAMA: KLIK TOMBOL PRINT REKAP ATAS (MEMBUKA MODAL GATEWAY)
    // ==========================================
    $(document)
        .off("click", "#btn-print-rekap-appkso")
        .on("click", "#btn-print-rekap-appkso", function () {
            let currentWh = $("#appkso-view-warehouse").val();

            if (!currentWh || currentWh === "") {
                Swal.fire({
                    title: "Gudang Belum Dipilih!",
                    text: "Silakan saring target gudang terlebih dahulu sebelum mencetak rekap master bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            let rowsData = window.cachedAppksoDetail || [];
            if (rowsData.length === 0) {
                Swal.fire({
                    title: "Data Master Kosong!",
                    text: "Tidak ada rekaman rincian data detail di gudang ini untuk dicetak bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            // Ekstrak daftar unik Operator dari live cache memori screen
            let uniqueOperators = [];
            let seenOpr = new Set();

            rowsData.forEach((row) => {
                if (row.opr && !seenOpr.has(row.opr)) {
                    seenOpr.add(row.opr);
                    uniqueOperators.push({
                        opr: row.opr,
                        oprname: row.oprname ?? "Tanpa Nama",
                    });
                }
            });

            // 🎯 FIX KUNCI: Urutkan daftar operator berdasarkan Oprname secara alfabetis A-Z (Ascending)
            uniqueOperators.sort((a, b) => {
                let nameA = a.oprname ? String(a.oprname).toLowerCase() : "";
                let nameB = b.omitname ? String(b.oprname).toLowerCase() : ""; // Pengaman typo property
                let realNameB = b.oprname
                    ? String(b.oprname).toLowerCase()
                    : "";
                return nameA.localeCompare(realNameB, undefined, {
                    sensitivity: "base",
                });
            });

            // Susun struktur opsi dropdown operator
            let dropdownOprSelect = $("#modal-filter-print-opr");
            dropdownOprSelect.html(
                '<option value="ALL" selected>-- CETAK SEMUA REKAP OPERATOR --</option>',
            );
            uniqueOperators.forEach((item) => {
                dropdownOprSelect.append(
                    `<option value="${item.opr}">${item.oprname.toUpperCase()} (${item.opr})</option>`,
                );
            });

            // Kosongkan form inputan tanggal murni agar diisi sendiri oleh operator
            $("#modal-filter-print-tgl-so, #modal-filter-print-tgl-posisi").val(
                "",
            );

            // Tampilkan Jendela Setup Filter Cetak Pop-Up
            let filterModal = new bootstrap.Modal(
                document.getElementById("modal-print-filter-gateway"),
            );
            filterModal.show();
        });

    // ==========================================
    // 🎯 2. SUBMIT FORM MODAL FILTER: PROSES SAN-RING DATA DAN PREVIEW ENGINE PRINTER
    // ==========================================
    $(document)
        .off("submit", "#form-trigger-print-rekap")
        .on("submit", "#form-trigger-print-rekap", function (e) {
            e.preventDefault();

            let targetWh = $("#appkso-view-warehouse").val();
            let filterOpr = $("#modal-filter-print-opr").val();
            let textOprSelected = $(
                "#modal-filter-print-opr option:selected",
            ).text();
            let tglSoInput = $("#modal-filter-print-tgl-so").val();
            let tglPosisiInput = $("#modal-filter-print-tgl-posisi").val();

            // 🔧 FIX KUNCI: Konversi format tanggal dari YYYY-MM-DD (HTML date input) ke DD/MM/YY
            let formatTgl = (dateStr) => {
                if (!dateStr) return "-";
                if (!dateStr.includes("-")) return dateStr;

                let [y, m, d] = dateStr.split("-");
                let tahun2digit = y.slice(-2); // Ambil 2 digit terakhir tahun
                return `${d}/${m}/${tahun2digit}`;
            };

            let displayTglSo = formatTgl(tglSoInput);
            let displayTglPosisi = formatTgl(tglPosisiInput);;

            // Filter data array master detail client-side
            let baseRows = window.cachedAppksoDetail || [];
            let filteredRows = [];

            if (filterOpr === "ALL") {
                filteredRows = baseRows;
            } else {
                filteredRows = baseRows.filter(
                    (row) => String(row.opr).trim() === filterOpr.trim(),
                );
            }

            if (filteredRows.length === 0) {
                Swal.fire({
                    title: "Hasil Saringan Kosong!",
                    text: "Operator terpilih tidak memiliki catatan pemindaian barang di gudang ini bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            // 🎯 FIX KUNCI 1: Urutkan data (Sort) berdasarkan No. Document (nokso) secara Ascending (A-Z / Angka Terkecil ke Terbesar)
            filteredRows.sort((a, b) => {
                let docA = a.nokso ? String(a.nokso).toLowerCase() : "";
                let docB = b.nokso ? String(b.nokso).toLowerCase() : "";
                return docA.localeCompare(docB, undefined, {
                    numeric: true,
                    sensitivity: "base",
                });
            });

            // Tutup Jendela Modal Setup Filter Cetak
            let filterModalEl = bootstrap.Modal.getInstance(
                document.getElementById("modal-print-filter-gateway"),
            );
            if (filterModalEl) filterModalEl.hide();

            // KALKULASI RE-SUM ACCUMULATED SECARA REAL-TIME
            let totalLembarKartu = filteredRows.length;
            let totalAccumulatedQty = 0;
            let tableRowsHTML = "";

            filteredRows.forEach((row, index) => {
                let qty = row.qty ? parseInt(row.qty) : 0;
                totalAccumulatedQty += qty;

                tableRowsHTML += `
                <tr style="border: 1px solid black;">
                    <td style="border: 1px solid black; text-align: center; font-family: monospace;">${index + 1}</td>
                    <td style="border: 1px solid black; text-align: center; font-family: monospace;">${row.nokso ?? "-"}</td>
                    <td style="border: 1px solid black; text-align: center; font-family: monospace;">${row.item ?? "-"}</td>
                    <td style="border: 1px solid black; text-align: left; padding-left: 8px; text-uppercase">${row.deskripsi ?? "-"}</td>
                    <td style="border: 1px solid black; text-align: right; padding-right: 8px; font-weight: bold;">${qty.toLocaleString("id-ID")}</td>
                    <td style="border: 1px solid black; text-align: left;"></td>
                </tr>
            `;
            });

            let labelPicCetak =
                filterOpr === "ALL" ? "Semua PIC Lapangan" : textOprSelected;

            // Buka lembar tab window anyar untuk memicu mesin printer browser Edge
            let printWindow = window.open("", "_blank");
            printWindow.document.open();
            printWindow.document.write("");
            printWindow.document.close();
            printWindow.document.open();

            printWindow.document.write(`
            <html>
            <head>
                <title>REKAP KARTU STOCK OPNAME - GUDANG ${targetWh.toUpperCase()}</title>
                <style>
                    @page {
                        size: A4 portrait;
                        margin-top: 8mm;
                        margin-right: 3mm;
                        margin-left: 3mm;
                        margin-bottom: 5mm;
                    }
                    body { font-family: Arial, sans-serif; padding: 5px; color: #000; background: #fff; font-size: 12px; }
                    table { width: 100%; border-collapse: collapse; }
                    .header-table td { border: none !important; padding: 4px !important; font-size: 12px !important; }
                    .main-data-table th { border: 1px solid black !important; background-color: #f2f2f2 !important; padding: 6px !important; font-size: 11px !important; text-transform: uppercase; }
                    .main-data-table td { padding: 5px !important; font-size: 11px !important; }
                    h4 { margin: 0; font-size: 16px; letter-spacing: 0.5px; font-weight: bold; }

                    /* 🎯 FIX KUNCI SAKTI: Cegah row total pecah nggantung di halaman tengah */
                    .row-total-akhir {
                        page-break-inside: avoid !important;
                    }
                </style>
            </head>
            <body>

                <div style="margin-bottom: 15px;">
                    <table class="header-table" style="width: 100%; border-collapse: collapse; border: none !important;">
                        <tr>
                            <td colspan="3" style="width: 400px; padding: 4px; border: none !important; vertical-align: middle;">
                                <h4 style="font-size: 16px !important; font-weight: bold; margin: 0; padding: 0; display: inline-block;">
                                    REKAP KARTU STOCK OPNAME
                                </h4>
                            </td>
                            <td style="width: 40px; border: none !important; padding: 4px;"></td>

                            <td rowspan="3" style="width: 200px; height: 75px; vertical-align: bottom; text-align: center; border-left: 1px solid black !important; border-right: 1px solid black !important; border-top: 1px solid black !important; border-bottom: 1px solid black !important; padding: 5px !important; font-size: 10px; font-weight: bold; background-color: #fff; text-transform: uppercase;">
                                ${filterOpr === "ALL" ? "SEMUA PIC" : (filteredRows[0].oprname ?? "SANG PIC")}
                            </td>

                            <td rowspan="3" style="width: 200px; height: 75px; vertical-align: bottom; text-align: center; border-left: 1px solid black !important; border-right: 1px solid black !important; border-top: 1px solid black !important; border-bottom: 1px solid black !important; padding: 5px !important; font-size: 10px; font-weight: bold; background-color: #fff; text-transform: uppercase;">
                                -
                            </td>
                        </tr>

                        <tr>
                            <td style="width: 150px; padding: 4px; border: none !important; font-size: 10px;">TGL STOCK OPNAME</td>
                            <td style="width: 5px; padding: 4px; border: none !important; text-align: center;">:</td>
                            <td style="padding: 4px; border: none !important; font-size: 10px; font-weight: bold;">${displayTglSo}</td>
                            <td style="border: none !important; padding: 4px;"></td>
                        </tr>

                        <tr>
                            <td style="width: 150px; padding: 4px; border: none !important; font-size: 10px;">TGL POSISI STOCK</td>
                            <td style="padding: 4px; border: none !important; text-align: center;">:</td>
                            <td style="padding: 4px; border: none !important; font-size: 10px; font-weight: bold;">${displayTglPosisi}</td>
                            <td style="border: none !important; padding: 4px;"></td>
                        </tr>

                        <tr>
                            <td style="width: 150px; padding: 4px; border: none !important; font-size: 10px;">JUMLAH KARTU STOCK</td>
                            <td style="padding: 4px; border: none !important; text-align: center;">:</td>
                            <td style="padding: 4px; border: none !important; font-size: 10px; font-weight: bold;">${totalLembarKartu} Lembar</td>
                            <td style="border: none !important; padding: 4px;"></td>

                            <td style="text-align: center; border-left: 1px solid black !important; border-right: 1px solid black !important; border-bottom: 1px solid black !important; border-top: 1px solid black !important; font-weight: bold; background-color: #fff; padding: 4px !important; font-size: 10px;">
                                Team Gud. Ban
                            </td>

                            <td style="text-align: center; border-left: 1px solid black !important; border-right: 1px solid black !important; border-bottom: 1px solid black !important; border-top: 1px solid black !important; font-weight: bold; background-color: #fff; padding: 4px !important; font-size: 10px;">
                                Team SO / Audit
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="main-data-table" style="border: 1px solid black;">
                    <thead>
                        <tr style="border: 1px solid black; text-align: center;">
                            <th style="width: 4%;">No</th>
                            <th style="width: 14%;">No. Document</th>
                            <th style="width: 14%;">Item Code</th>
                            <th style="width: 35%; text-align: left; padding-left: 8px;">Description</th>
                            <th style="width: 13%; text-align: right; padding-right: 8px;">Qty</th>
                            <th style="width: 20%;">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRowsHTML}

                        <tr class="row-total-akhir" style="border: 1px solid black; font-weight: bold; background-color: #f2f2f2;">
                            <td colspan="4" style="border: 1px solid black; text-align: center; font-size: 13px; letter-spacing: 1px;">TOTAL</td>
                            <td style="border: 1px solid black; text-align: right; padding-right: 8px; font-family: monospace; font-size: 14px !important; font-weight: 900;">
                                ${totalAccumulatedQty.toLocaleString("id-ID")}
                            </td>
                            <td style="border: 1px solid black;"></td>
                        </tr>
                    </tbody>
                </table>

            </body>
            </html>
        `);

            printWindow.document.close();

            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
            }, 350);
        });

    // ==========================================
    // 4. JALUR LIVE SEARCH GLOBAL REAL-TIME
    // ==========================================
    $(document)
        .off("keyup", "#search-table-global")
        .on("keyup", "#search-table-global", function () {
            let value = $(this).val().toLowerCase().trim();

            if (value === "") {
                $(".appkso-data-row").show();
                return;
            }

            $(".appkso-data-row").each(function () {
                let opr = $(this).attr("data-opr");
                let oprname = $(this).attr("data-oprname");
                let nokso = $(this).attr("data-nokso");
                let item = $(this).attr("data-item");

                if (
                    opr.includes(value) ||
                    oprname.includes(value) ||
                    nokso.includes(value) ||
                    item.includes(value)
                ) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

    // ==========================================
    // 5. LOGIKA FORM SUBMIT IMPORT EXCEL
    // ==========================================
    $("#form-upload-appkso")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();

            let uploadWh = $("#appkso-upload-warehouse").val();
            if (!uploadWh) {
                Swal.fire({
                    title: "Target Kosong!",
                    text: "Pilih gudang tujuan upload terlebih dahulu bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            let fileInput = document.getElementById("file-excel");
            if (fileInput.files.length === 0) {
                Swal.fire({
                    title: "Berkas Kosong!",
                    text: "File Excel opname APPKSO belum lu pilih!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            Swal.fire({
                title: "Membongkar Berkas Excel",
                html: "Sistem sedang menyisir data lokal berkas lu... <br><strong>Mohon tunggu sejenak!</strong>",
                allowOutsideClick: false,
                onOpen: () => {
                    Swal.showLoading();
                },
            });

            let file = fileInput.files[0];
            let reader = new FileReader();

            reader.onload = function (e) {
                let buffer = e.target.result;
                let workbook = new ExcelJS.Workbook();

                workbook.xlsx
                    .load(buffer)
                    .then(function () {
                        let worksheet = workbook.worksheets[0];
                        let sheetRows = [];

                        worksheet.eachRow(
                            { includeEmpty: false },
                            function (row, rowNumber) {
                                let rowData = [];
                                row.eachCell(
                                    { includeEmpty: true },
                                    function (cell, colNumber) {
                                        let val = cell.value;
                                        if (
                                            val &&
                                            typeof val === "object" &&
                                            val.result !== undefined
                                        ) {
                                            val = val.result;
                                        }
                                        rowData[colNumber - 1] = val;
                                    },
                                );
                                sheetRows.push(rowData);
                            },
                        );

                        if (sheetRows.length <= 1) {
                            Swal.fire({
                                title: "Impor Gagal",
                                text: "Struktur isi berkas Excel kosong bro!",
                                confirmButtonColor: "#fe6807",
                            });
                            return;
                        }

                        let sampleItem = "";
                        if (sheetRows[1] && sheetRows[1][4]) {
                            sampleItem = String(sheetRows[1][4]).trim();
                        }

                        if (sampleItem === "") {
                            Swal.fire({
                                title: "Item Tidak Terdeteksi",
                                text: "Baris kode ITEM baris pertama kosong!",
                                confirmButtonColor: "#fe6807",
                            });
                            return;
                        }

                        $(".swal2-title").text("Menyuntik Database");
                        $(".swal2-content").html(
                            "Sedang membilas data lama & menyuntikkan data APPKSO baru masal...<br><strong>Gudang Target: " +
                            uploadWh +
                            "</strong>",
                        );

                        $.ajax({
                            url: "/oracle-fisik/appkso/import",
                            type: "POST",
                            data: JSON.stringify({
                                warehouse: uploadWh,
                                excel_data: sheetRows,
                                sample_item: sampleItem,
                            }),
                            contentType: "application/json",
                            dataType: "json",
                            success: function (res) {
                                Swal.close();

                                Swal.fire({
                                    title: "MANTAP KILAT!",
                                    text: res.message,
                                    confirmButtonColor: "#fe6807",
                                    timer: 4000,
                                });

                                $("#form-upload-appkso")[0].reset();
                                $("#text-file-excel").text(
                                    "Klik atau seret file Excel ke sini",
                                );

                                $("#appkso-view-warehouse")
                                    .val(uploadWh)
                                    .trigger("change");
                            },
                            error: function (xhr) {
                                Swal.close();
                                let errMsg = xhr.responseJSON
                                    ? xhr.responseJSON.message
                                    : "Gagal memproses berkas server.";
                                Swal.fire({
                                    title: "Validasi Rejected!",
                                    text: errMsg,
                                    confirmButtonColor: "#d33",
                                });
                            },
                        });
                    })
                    .catch(function (err) {
                        Swal.close();
                        Swal.fire({
                            title: "ExcelJS Error",
                            text: err.message,
                            confirmButtonColor: "#fe6807",
                        });
                    });
            };

            reader.readAsArrayBuffer(file);
        });

    $(document)
        .off("change", "#file-excel")
        .on("change", "#file-excel", function () {
            let filename = this.files[0]
                ? this.files[0].name
                : "Klik atau seret file Excel ke sini";
            $("#text-file-excel").text(filename);
        });

    loadAppksoViewFilters();

    // ==========================================
    // 🖨️ PANEL B: NEW FEATURE LOGIKA PRINT KSO CARDS (BARCODE LABELS)
    // ==========================================
    $(document)
        .off("click", "#btn-print-rekap-kso")
        .on("click", "#btn-print-rekap-kso", function () {
            let currentWh = $("#appkso-view-warehouse").val();
            if (!currentWh) {
                Swal.fire({
                    title: "Gudang Belum Dipilih!",
                    text: "Saring target gudang terlebih dahulu bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            let rowsData = window.cachedAppksoDetail || [];
            if (rowsData.length === 0) {
                Swal.fire({
                    title: "Data Kosong!",
                    text: "Tidak ada rekaman data kso untuk dicetak!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            // Bangun Map relasi unik PIC -> list jangkauan No KSO
            let picMap = {};
            rowsData.forEach((row) => {
                let pCode = row.opr;
                let pName = row.oprname ?? "Tanpa Nama";
                if (pCode) {
                    if (!picMap[pCode]) {
                        picMap[pCode] = { name: pName, docs: new Set() };
                    }
                    if (row.nokso) picMap[pCode].docs.add(row.nokso);
                }
            });

            let picSelect = $("#modal-print-kso-gateway").find(
                "#modal-kso-print-pic",
            );
            picSelect.html(
                '<option value="" disabled selected>-- PILIH PIC LAPANGAN --</option>',
            );

            Object.keys(picMap)
                .sort((a, b) => picMap[a].name.localeCompare(picMap[b].name))
                .forEach((code) => {
                    picSelect.append(
                        `<option value="${code}" data-docs='${JSON.stringify(Array.from(picMap[code].docs).sort())}'>${picMap[code].name.toUpperCase()} (${code})</option>`,
                    );
                });

            $("#modal-print-kso-gateway")
                .find("#modal-kso-print-doc-from, #modal-kso-print-doc-to")
                .html('<option value="">⏳ Pilih PIC Dulu</option>');
            $("#modal-print-kso-gateway")
                .find("#modal-kso-print-tanggal-manual")
                .val("");

            let ksoModal = new bootstrap.Modal(
                document.getElementById("modal-print-kso-gateway"),
            );
            ksoModal.show();
        });

    $(document)
        .off("change", "#modal-kso-print-pic")
        .on("change", "#modal-kso-print-pic", function () {
            let selectedOption = $(this).find("option:selected");
            let docs = JSON.parse(selectedOption.attr("data-docs") || "[]");

            let fromSelect = $("#modal-kso-print-doc-from");
            let toSelect = $("#modal-kso-print-doc-to");

            fromSelect.html(
                '<option value="" disabled selected>-- PILIH DOC AWAL --</option>',
            );
            toSelect.html(
                '<option value="" disabled selected>-- PILIH DOC AKHIR --</option>',
            );

            docs.forEach((doc) => {
                fromSelect.append(`<option value="${doc}">${doc}</option>`);
                toSelect.append(`<option value="${doc}">${doc}</option>`);
            });
        });

    $(document)
        .off("change", "#modal-kso-print-doc-from")
        .on("change", "#modal-kso-print-doc-from", function () {
            let fromVal = $(this).val();
            let selectedOption = $("#modal-kso-print-pic").find(
                "option:selected",
            );
            let docs = JSON.parse(selectedOption.attr("data-docs") || "[]");

            let toSelect = $("#modal-kso-print-doc-to");
            toSelect.html(
                '<option value="" disabled selected>-- PILIH DOC AKHIR --</option>',
            );

            docs.filter((doc) => doc >= fromVal).forEach((doc) => {
                toSelect.append(`<option value="${doc}">${doc}</option>`);
            });
        });

    $(document)
        .off("submit", "#form-trigger-print-kso-cards")
        .on("submit", "#form-trigger-print-kso-cards", function (e) {
            e.preventDefault();

            let picCode = $("#modal-kso-print-pic").val();
            let docFrom = $("#modal-kso-print-doc-from").val();
            let docTo = $("#modal-kso-print-doc-to").val();

            // 🔧 FIX: Konversi tanggal dari YYYY-MM-DD ke DD / BULAN / YYYY format manual
            let tglInputRaw = $("#modal-kso-print-tanggal-manual").val();
            let tglManual = "";

            if (tglInputRaw) {
                let [tahun, bulan, tanggal] = tglInputRaw.split("-");
                let bulanNama = [
                    "JANUARI", "FEBRUARI", "MARET", "APRIL", "MEI", "JUNI",
                    "JULI", "AGUSTUS", "SEPTEMBER", "OKTOBER", "NOVEMBER", "DESEMBER"
                ];
                let nmBulan = bulanNama[parseInt(bulan) - 1];
                tglManual = `${parseInt(tanggal)} / ${nmBulan} / ${tahun}`;
            }

            let baseRows = window.cachedAppksoDetail || [];
            let matchedCards = baseRows.filter((row) => {
                return (
                    String(row.opr).trim() === picCode.trim() &&
                    row.nokso >= docFrom &&
                    row.nokso <= docTo
                );
            });

            if (matchedCards.length === 0) {
                Swal.fire({
                    title: "Data Tidak Ditemukan!",
                    text: "Jangkauan range nomor dokumen kosong bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }

            matchedCards.sort((a, b) =>
                String(a.nokso).localeCompare(String(b.nokso), undefined, {
                    numeric: true,
                }),
            );

            let ksoModalEl = bootstrap.Modal.getInstance(
                document.getElementById("modal-print-kso-gateway"),
            );
            if (ksoModalEl) ksoModalEl.hide();

            // 🔧 Mapping Gudang ke Plant Code
            let plantMap = {
                "APW": "A",
                "BPW": "B",
                "DPW": "D",
                "RPW": "R"
            };

            let currentWh = $("#appkso-view-warehouse").val();
            let plantCode = plantMap[currentWh] || "B"; // Default B kalau gak ketemu

            let cardsHTML = "";

            // 🎯 CORETAN BULATAN HITAM REAL-TIME
            let getCircleStyle = (digit, targetVal) => {
                if (digit !== null && parseInt(digit) === parseInt(targetVal)) {
                    return "display:inline-block; text-align:center; height:8px; width:8px; border-radius:50%; border: 8px solid #000; color:transparent !important; background-color:#000 !important; box-sizing:border-box;";
                }
                return "";
            };

            // Looping membentuk lembar halaman per-item KSO
            matchedCards.forEach((t) => {
                let qtyStr = String(t.qty || "0");
                let len = qtyStr.length;
                let getDigit = (idx) =>
                    len - idx >= 0 ? parseInt(qtyStr[len - idx]) : null;

                let pRibu = getDigit(5),
                    ribu = getDigit(4),
                    ratus = getDigit(3),
                    puluh = getDigit(2),
                    sat = getDigit(1);

                let gradeStr = String(t.item).endsWith("0") ? "OE" : "OK";

                // 🎯 REVISI SAKLEK 1: Fungsi pembentuk deretan angka 1-9-0 tanpa label teks kiri
                let buildCircleRow = (digitValue) => {
                    // 1. Container flex tetap, kita kasih position: relative biar lingkaran absolute bisa nangkring
                    let html = `<div style="display:flex; justify-content:space-between; width:300px;">`;

                    // Fungsi untuk render satu digit angka
                    let renderDigit = (v) => {
                        let isSelected =
                            digitValue !== null &&
                            parseInt(digitValue) === parseInt(v);

                        // 🎯 KUNCI:
                        // - Angka tetap di posisi aslinya
                        // - Lingkaran hitam absolute (z-index -1 biar di belakang angka atau 1 kalau mau nutupin)
                        // - Menggunakan flex center agar angka selalu di tengah lingkaran
                        return `
             <div style="position:relative; width:16px; height:16px; display:flex; align-items:center; justify-content:center; font-family:Arial; font-weight:bold; font-size:12px;">
        ${isSelected ? `<div style="position:absolute; width:16px; height:16px; border-radius:50%; background:#000; z-index:1;"></div>` : `<span style="position:relative; z-index:1; color:#000">${v}</span>`}
    </div>
        `;
                    };

                    for (let i = 1; i <= 9; i++) {
                        html += renderDigit(i);
                    }
                    html += renderDigit(0);
                    html += `</div>`;

                    return html;
                };

                cardsHTML += `
                <div class="page-break" style="position: relative; min-height: 100vh;">


                    <table class="mb-1" style="width:100%; border-collapse:collapse; font-size:14px; border: none !important;">
                        <tr>
                            <td colspan="6"
                                style="border:none; text-align:center; font-weight:bold; font-size:20px; font-family: 'Times New Roman', Times, serif; position: relative;">
                                KARTU STOCK OPNAME
                                <p style="font-size:13px; margin:0; line-height:1; font-family: 'Times New Roman', Times, serif; font-weight: normal;">TANGGAL : ${tglManual}</p>
                                <p style="font-size:23px; padding-top:10px; line-height:1;margin-bottom:0; font-family: 'Times New Roman', Times, serif;">${gradeStr}</p>

                                <div class="barcode" style="position: absolute; top: 0; right: 0; text-align: center; font-weight: normal;">
                                    <div style="font-size:12px; font-family: 'Times New Roman', Times, serif;">KODE DOKUMEN & NO. DOC</div>
                                    <div style="font-size:40px; line-height:1; font-family: 'Libre Barcode 39'; font-weight: normal;">*${t.nokso}*</div>
                                    <div style="font-size:12px; font-family: 'Times New Roman', Times, serif; position: relative; top: -15px;">${t.nokso}</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">PT GAJAH TUNGGAL Tbk</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; white-space: nowrap; font-family: 'Times New Roman', Times, serif;">BARANG MILIK PLANT</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">: <b>${plantCode}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding-bottom:1px; text-align:left; vertical-align: top; text-indent:5px; font-weight:bold; font-size:18px; font-family: 'Times New Roman', Times, serif;"><b>${t.deskripsi ?? "-"}</b></td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">NO. DOCUMENT</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">: <b>${t.nokso}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; font-size:50px; text-align:left; text-indent:5px; font-family: 'Libre Barcode 39', cursive; font-weight: normal;">*${t.item}*</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; vertical-align:top; text-indent:5px; font-family: 'Times New Roman', Times, serif;">NO. INDEX</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif; vertical-align:middle;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:1px; position: relative; top: -20px;">:</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; font-weight:bold; padding-bottom:1px; font-size:10px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">${t.item}</td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">LINE NUMBER</td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">GRADE</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${gradeStr}</b></td>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">PLANT : <b>${plantCode}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan_ribu">${buildCircleRow(pRibu)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">JENIS</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">:</td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ribuan">${buildCircleRow(ribu)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">UKURAN</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${t.deskripsi ?? "-"}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ratusan">${buildCircleRow(ratus)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">CODE</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${t.item}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan">${buildCircleRow(puluh)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">JUMLAH</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif; white-space: nowrap;">
                                : <b>${Number(t.qty).toLocaleString("id-ID")} PCS</b>
                                <span style="font-family: 'Libre Barcode 39'; font-size:30px; line-height:1; font-weight: normal; margin-left:10px; position: relative; top: 5px;">*${t.qty}*</span>
                            </td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="satuan">${buildCircleRow(sat)}</div>
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
                            <td style="width:10%;padding-bottom:50px; border:none;"></td><td style="width:10%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;"><div style="margin:2px auto; text-align:center; font-family: 'Times New Roman', Times, serif;"><b>${t.oprname ?? "-"}</b></div></td>
                            <td colspan="2" style="padding-top:15px; border:none;"></td>
                            <td colspan="2" style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;"><div style="margin:1px auto; text-align:center; font-family: 'Times New Roman', Times, serif;"><b>..........</b></div></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;"><div style="width:80%; margin:1px auto; border-top:2px solid #000; text-align:center;">GUDANG BAN</div></td>
                            <td colspan="2" style="border:none;"></td>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;"><div style="width:60%; margin:1px auto; border-top:2px solid #000; text-align:center;">TEAM S.O./AUDITOR</div></td>
                        </tr>
                        <tr>
                            <td colspan="6" style="position: relative; border:none !important;"><img src="/images/garis_gunting.png" alt="Garis Gunting" style="width: 100%; height: 50px; position: relative; top: 27px;"></td>
                        </tr>
                     </table>

                    <table class="mb-1" style="width:100%; border-collapse:collapse; font-size:14px; border: none !important;">
                       <td colspan="4" style="border:none; font-size:50px; text-align:left; text-indent:5px; font-weight: normal;">
                             <span style="visibility:hidden; font-family: 'Libre Barcode 39', cursive;">*${t.item}*</span>
                        </td>
                        <tr>
                            <td colspan="6"
                                style="border:none; text-align:center; font-weight:bold; font-size:20px; font-family: 'Times New Roman', Times, serif; position: relative;">
                                KARTU STOCK OPNAME
                                <p style="font-size:13px; margin:0; line-height:1; font-family: 'Times New Roman', Times, serif; font-weight: normal;">TANGGAL : ${tglManual}</p>
                                <p style="font-size:23px; padding-top:10px; line-height:1;margin-bottom:0; font-family: 'Times New Roman', Times, serif;">${gradeStr}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">PT GAJAH TUNGGAL Tbk</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; white-space: nowrap; font-family: 'Times New Roman', Times, serif;">BARANG MILIK PLANT</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">: <b>${plantCode}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; padding-bottom:1px; text-align:left; vertical-align: top; text-indent:5px; font-weight:bold; font-size:18px; font-family: 'Times New Roman', Times, serif;"><b>${t.deskripsi ?? "-"}</b></td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">NO. DOCUMENT</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:2px;">: <b>${t.nokso}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; font-size:50px; text-align:left; text-indent:5px; font-weight: normal;">
                             <span style="visibility:hidden; font-family: 'Libre Barcode 39', cursive;">*${t.item}*</span>
                            </td>
                            <td style="border:none; padding-bottom:1px; text-align:left; vertical-align:top; text-indent:5px; font-family: 'Times New Roman', Times, serif;">NO. INDEX</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; white-space:nowrap; font-family:'Times New Roman', Times, serif; vertical-align:middle;">
                                <span style="display:inline-block; width:100px; margin-left:60px; border-bottom:1px solid #000; padding-bottom:1px; position: relative; top: -20px;">:</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" style="border:none; font-weight:bold; padding-bottom:1px; font-size:10px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">${t.item}</td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;">LINE NUMBER</td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">GRADE</td>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${gradeStr}</b></td>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">PLANT : <b>${plantCode}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan_ribu">${buildCircleRow(pRibu)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">JENIS</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">:</td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ribuan">${buildCircleRow(ribu)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">UKURAN</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${t.deskripsi ?? "-"}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="ratusan">${buildCircleRow(ratus)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">CODE</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">: <b>${t.item}</b></td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="puluhan">${buildCircleRow(puluh)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif;">JUMLAH</td>
                            <td colspan="3" style="border:none; padding-bottom:1px; text-align:left; text-indent:5px; font-family: 'Times New Roman', Times, serif; white-space: nowrap;">
                                : <b>${Number(t.qty).toLocaleString("id-ID")} PCS</b>
                               <span style="visibility:hidden; font-family: 'Libre Barcode 39'; font-size:30px; line-height:1; font-weight: normal; margin-left:10px; position: relative; top: 5px;">*${t.qty}*</span>
                            </td>
                            <td colspan="2" style="border:none; font-weight:bold; padding-bottom:1px; text-align:center; text-indent:5px;">
                                <div id="satuan">${buildCircleRow(sat)}</div>
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
                            <td style="width:10%;padding-bottom:50px; border:none;"></td><td style="width:10%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td><td style="width:20%;padding-bottom:30px; border:none;"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;"><div style="margin:2px auto; text-align:center; font-family: 'Times New Roman', Times, serif;"><b>${t.oprname ?? "-"}</b></div></td>
                            <td colspan="2" style="padding-top:15px; border:none;"></td>
                            <td colspan="2" style="padding-top:15px;border:none; padding-bottom:1px; text-align:center; text-indent:5px;"><div style="margin:1px auto; text-align:center; font-family: 'Times New Roman', Times, serif;"><b>..........</b></div></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;"><div style="width:80%; margin:1px auto; border-top:2px solid #000; text-align:center;">GUDANG BAN</div></td>
                            <td colspan="2" style="border:none;"></td>
                            <td colspan="2" style="border:none; padding-bottom:1px; text-align:center; text-indent:5px; font-family: 'Times New Roman', Times, serif;"><div style="width:60%; margin:1px auto; border-top:2px solid #000; text-align:center;">TEAM S.O./AUDITOR</div></td>
                        </tr>

                     </table>
                </div>
                `;
            });

            let previewWindow = window.open(
                "",
                "_blank",
                "width=1200,height=800",
            );
            previewWindow.document.write(`
                <html>
                <head>
                    <title>Print Kartu KSO Barcode - PIC ${picCode}</title>
                    <style>
                        @font-face {
                            font-family: 'Libre Barcode 39';
                            src: url('/fonts/LibreBarcode39-Regular.ttf') format('truetype');
                        }
                        @page { size: A4 portrait; margin: 10mm 4mm 0mm 4mm; }
                        body { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        td { border:none !important; }
                        .page-break { page-break-after: always; position: relative; }
                    </style>
                </head>
                <body>
                    ${cardsHTML}
                </body>
                </html>
            `);

            previewWindow.document.close();
            previewWindow.document.fonts.ready.then(() => {
                setTimeout(() => {
                    previewWindow.focus();
                    previewWindow.print();
                }, 350);
            });
        });
});
