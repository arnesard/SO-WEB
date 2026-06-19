$(document).ready(function () {
    loadData();

    $("#search-item").on("keypress", function (e) {
        if (e.which === 13) {
            loadData();
        }
    });
});

// Fungsi buat pemisah ribuan (Format Indonesia -> titik)
function formatNumber(num) {
    if (!num && num !== 0) return "-";
    return new Intl.NumberFormat("id-ID").format(num);
}

function loadData() {
    let tbody = $("#tbody-so-karantina");
    let filterGrade = $("#filter-grade").val();
    let filterKeterangan = $("#filter-keterangan").val();
    let searchItem = $("#search-item").val();

    tbody.html(
        '<tr><td colspan="10" class="text-center text-muted py-4">Memuat data...</td></tr>',
    );

    $.ajax({
        url: "/so-karantina/data",
        type: "GET",
        data: {
            grade: filterGrade,
            keterangan: filterKeterangan,
            search: searchItem,
        },
        dataType: "json",
        success: function (response) {
            window.dataExportPlant = response.exportPlant || [];
            let html = "";

            // Variabel Penampung Grand Total Tabel
            let sumShift1 = 0,
                sumShift2 = 0,
                sumShift3 = 0;
            let sumTotal = 0,
                sumScan = 0,
                sumVariance = 0;
            let skuCount = 0;

            if (response.data && response.data.length > 0) {
                skuCount = response.data.length;

                $.each(response.data, function (index, item) {
                    // Penjumlahan Grand Total
                    sumShift1 += parseInt(item.shift_1);
                    sumShift2 += parseInt(item.shift_2);
                    sumShift3 += parseInt(item.shift_3);
                    sumTotal += parseInt(item.total);
                    sumScan += parseInt(item.hasil_scan);
                    sumVariance += parseInt(item.variance);

                    let badgeClass =
                        item.keterangan === "Sesuai"
                            ? "bg-success"
                            : "bg-danger";
                    let varianceColor =
                        item.variance !== 0 ? "text-danger fw-bold" : "";

                    html += `
                        <tr style="cursor: pointer;" onclick="openScanDetailModal('${item.item_code}')">
                            <td>${item.no}</td>
                            <td class="text-start">${item.item_code}</td>
                            <td class="text-start text-muted">${item.item_description}</td>
                            <td>${formatNumber(item.shift_1)}</td>
                            <td>${formatNumber(item.shift_2)}</td>
                            <td>${formatNumber(item.shift_3)}</td>
                            <td class="fw-bold bg-light">${formatNumber(item.total)}</td>
                            <td class="fw-bold text-primary bg-light">${formatNumber(item.hasil_scan)}</td>
                            <td class="${varianceColor}">${formatNumber(item.variance)}</td>
                            <td><span class="badge ${badgeClass}">${item.keterangan}</span></td>
                        </tr>
                    `;
                });
            } else {
                html =
                    '<tr><td colspan="10" class="text-center py-4">Tidak ada data ditemukan.</td></tr>';
            }

            tbody.html(html);

            // Print Grand Total ke Footer Tabel
            $("#tfoot-sku").text(formatNumber(skuCount));
            $("#tfoot-shift1").text(formatNumber(sumShift1));
            $("#tfoot-shift2").text(formatNumber(sumShift2));
            $("#tfoot-shift3").text(formatNumber(sumShift3));
            $("#tfoot-total").text(formatNumber(sumTotal));
            $("#tfoot-scan").text(formatNumber(sumScan));
            $("#tfoot-variance").text(formatNumber(sumVariance));

            // Print Data Summary Cards (OE, OK, Plant)
            if (response.summaryOE) {
                $("#card-oe-qty").text(formatNumber(response.summaryOE.total));
                $("#card-oe-sku").text(formatNumber(response.summaryOE.sku));
            }
            if (response.summaryOK) {
                $("#card-ok-qty").text(formatNumber(response.summaryOK.total));
                $("#card-ok-sku").text(formatNumber(response.summaryOK.sku));
            }

            // Print Variance Cards
            if (response.varOE) {
                $("#card-var-oe-qty").text(formatNumber(response.varOE.total));
                $("#card-var-oe-sku").text(formatNumber(response.varOE.sku));
            }
            if (response.varOK) {
                $("#card-var-ok-qty").text(formatNumber(response.varOK.total));
                $("#card-var-ok-sku").text(formatNumber(response.varOK.sku));
            }

            // Reset dulu card plant biar kalau difilter kembali nol
            $(
                "#card-plant-b-qty, #card-plant-h-qty, #card-plant-i-qty, #card-plant-t-qty",
            ).text("0");
            $(
                "#card-plant-b-sku, #card-plant-h-sku, #card-plant-i-sku, #card-plant-t-sku",
            ).text("0");

            ["b", "h", "i", "t"].forEach((p) => {
                $(`#card-plant-${p}-qty`).text("0");
                $(`#card-plant-${p}-sku`).text("0");
                $(`#card-plant-${p}-grades`).html("");
            });

            // Mapping Response Plant (Lengkap dengan rincian Grade)
            if (response.summaryPlant) {
                $.each(response.summaryPlant, function (plantCode, data) {
                    let p = plantCode.toLowerCase(); // "b", "h", "i", atau "t"

                    if (["b", "h", "i", "t"].includes(p)) {
                        // Print Total Qty & SKU Plant
                        $(`#card-plant-${p}-qty`).text(
                            formatNumber(data.total),
                        );
                        $(`#card-plant-${p}-sku`).text(formatNumber(data.sku));

                        // Print Rincian Grade
                        let gradesHtml = "";
                        if (data.grades && data.grades.length > 0) {
                            $.each(data.grades, function (i, g) {
                                gradesHtml += `
                                    <div class="d-flex justify-content-between text-muted mb-1">
                                        <span>Grade ${g.grade}</span>
                                        <span class="fw-bold text-dark">${formatNumber(g.qty)} <span class="fw-normal text-muted" style="font-size: 0.6rem;">(${formatNumber(g.sku)})</span></span>
                                    </div>
                                `;
                            });
                        }
                        $(`#card-plant-${p}-grades`).html(gradesHtml);
                    }
                });
            }
            // Re-init lucide icons (kalau iconnya ada di dalam row hasil fetch)
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        },
        error: function (xhr, status, error) {
            console.error("Error fetching data:", error);
            tbody.html(
                '<tr><td colspan="10" class="text-center text-danger py-4">Gagal memuat data dari server.</td></tr>',
            );
        },
    });
}

// ==========================================
// 🎯 EXPORT EXCEL (Menggunakan ExcelJS)
// ==========================================
async function exportExcel() {
    Swal.fire({
        title: "Menyiapkan Excel...",
        text: "Merapikan data dan format tabel per Plant",
        allowOutsideClick: false,
        onOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const wb = new ExcelJS.Workbook();

        const borderThin = {
            top: { style: "thin" },
            left: { style: "thin" },
            bottom: { style: "thin" },
            right: { style: "thin" },
        };
        const fontBold12 = { name: "Arial", size: 12, bold: true };
        const fontBold10 = { name: "Arial", size: 10, bold: true };
        const fontNormal10 = { name: "Arial", size: 10 };
        const alignCenter = { vertical: "middle", horizontal: "center" };
        const alignLeft = { vertical: "middle", horizontal: "left" };
        const alignRight = { vertical: "middle", horizontal: "right" };

        let currentYear = new Date().getFullYear();

        // =========================================================================
        // SHEET 1: Form Karantina FGW - Rev.0 (Dari UI Table)
        // =========================================================================
        const ws1 = wb.addWorksheet("Form Karantina FGW - Rev.0");

        ws1.columns = [
            { key: "A", width: 6 },
            { key: "B", width: 20 },
            { key: "C", width: 45 },
            { key: "D", width: 35 },
            { key: "E", width: 15 },
        ];

        ws1.mergeCells("A1:E1");
        ws1.getCell("A1").value =
            "FORM REKAP PERHITUNGAN STOCK OPNAME AREA KARANTINA";
        ws1.getCell("A1").font = fontBold12;
        ws1.getCell("A1").alignment = alignCenter;

        ws1.mergeCells("A2:E2");
        ws1.getCell("A2").value = "PT GAJAH TUNGGAL Tbk";
        ws1.getCell("A2").font = fontBold10;
        ws1.getCell("A2").alignment = alignLeft;

        ws1.getCell("A4").value = "SEMESTER / TAHUN";
        ws1.getCell("B4").value = `: ${currentYear}`;
        ws1.getCell("A5").value = "LOKASI GUDANG";
        ws1.getCell("B5").value = ": Gudang Ban B Dept.";
        ws1.getCell("A6").value = "ID SO";
        ws1.getCell("B6").value = ": ";
        ws1.getCell("A7").value = "PENDAMPING";
        ws1.getCell("B7").value = ": ";
        ws1.getCell("A8").value = "AUDITOR";
        ws1.getCell("B8").value = ": ";

        ["A4", "A5", "A6", "A7", "A8"].forEach(
            (c) => (ws1.getCell(c).font = fontBold10),
        );
        ["B4", "B5", "B6", "B7", "B8"].forEach(
            (c) => (ws1.getCell(c).font = fontNormal10),
        );

        // Kotak Tanda Tangan
        ws1.getCell("D4").value = "Gudang";
        ws1.getCell("E4").value = "Auditor";
        ws1.getCell("D4").font = fontBold10;
        ws1.getCell("E4").font = fontBold10;
        ws1.getCell("D4").alignment = alignCenter;
        ws1.getCell("E4").alignment = alignCenter;

        ws1.mergeCells("D5:D7");
        ws1.mergeCells("E5:E7");
        ws1.getCell("D8").value = "(....................)";
        ws1.getCell("D8").alignment = alignCenter;
        ws1.getCell("E8").value = "(....................)";
        ws1.getCell("E8").alignment = alignCenter;

        for (let r = 4; r <= 8; r++) {
            ws1.getCell(`D${r}`).border = borderThin;
            ws1.getCell(`E${r}`).border = borderThin;
        }

        let row10 = ws1.getRow(10);
        let h1 = [
            "NO",
            "ITEM CODE",
            "ITEM DESCRIPTION",
            "PERINCIAN HITUNGAN",
            "TOTAL",
        ];
        h1.forEach((text, i) => {
            let c = row10.getCell(i + 1);
            c.value = text;
            c.font = fontBold10;
            c.alignment = alignCenter;
            c.border = borderThin;
        });

        let startR1 = 11;
        let idxAll = 0;

        $("#tbody-so-karantina tr").each(function () {
            let tds = $(this).find("td");
            if (tds.length > 1) {
                let no = tds.eq(0).text().trim();
                let code = tds.eq(1).text().trim();
                let desc = tds.eq(2).text().trim();

                let r1 = ws1.getRow(startR1 + idxAll);
                r1.getCell(1).value = Number(no);
                r1.getCell(2).value = code;
                r1.getCell(3).value = desc;
                r1.getCell(4).value = "";
                r1.getCell(5).value = "";

                r1.getCell(1).alignment = alignCenter;
                r1.getCell(2).alignment = alignLeft;
                r1.getCell(3).alignment = alignLeft;
                for (let c = 1; c <= 5; c++) {
                    r1.getCell(c).border = borderThin;
                    r1.getCell(c).font = fontNormal10;
                }
                idxAll++;
            }
        });

        let footR1 = startR1 + idxAll;
        ws1.mergeCells(`A${footR1}:D${footR1}`);
        let fC1 = ws1.getCell(`A${footR1}`);
        fC1.value = "GRAND TOTAL";
        fC1.font = fontBold10;
        fC1.alignment = alignCenter;
        for (let c = 1; c <= 5; c++) ws1.getCell(footR1, c).border = borderThin;

        // =========================================================================
        // FUNGSI HELPER UNTUK SHEET REKAP BSTB (ALL & PER PLANT)
        // =========================================================================
        function createRekapSheet(sheetName, isAll, plantCode = "") {
            const ws = wb.addWorksheet(sheetName);
            ws.columns = [
                { key: "A", width: 6 },
                { key: "B", width: 20 },
                { key: "C", width: 45 },
                { key: "D", width: 12 },
                { key: "E", width: 12 },
                { key: "F", width: 12 },
                { key: "G", width: 15 },
            ];

            ws.mergeCells("A1:G1");
            ws.getCell("A1").value =
                "FORM REKAP BSTB" +
                (isAll ? " (ALL PLANT)" : ` PLANT ${plantCode}`);
            ws.getCell("A1").font = fontBold12;
            ws.getCell("A1").alignment = alignCenter;

            ws.mergeCells("A2:G2");
            ws.getCell("A2").value = "PT GAJAH TUNGGAL Tbk";
            ws.getCell("A2").font = fontBold10;
            ws.getCell("A2").alignment = alignLeft;

            ws.getCell("A4").value = "SEMESTER / TAHUN";
            ws.getCell("C4").value = `: ${currentYear}`;
            ws.getCell("A5").value = "LOKASI GUDANG";
            ws.getCell("C5").value = ": Gudang Ban B Dept.";
            ws.mergeCells("A4:B4");
            ws.mergeCells("A5:B5");
            ws.mergeCells("C4:G4");
            ws.mergeCells("C5:G5");

            ws.getCell("A4").font = fontBold10;
            ws.getCell("A5").font = fontBold10;
            ws.getCell("C4").font = fontNormal10;
            ws.getCell("C5").font = fontNormal10;

            for (let r = 4; r <= 5; r++) {
                for (let c = 1; c <= 7; c++)
                    ws.getCell(r, c).border = borderThin;
            }

            let row7 = ws.getRow(7);
            let h2 = [
                "NO",
                "ITEM CODE",
                "ITEM DESCRIPTION",
                "Shift 1",
                "Shift 2",
                "Shift 3",
                "TOTAL",
            ];
            h2.forEach((text, i) => {
                let c = row7.getCell(i + 1);
                c.value = text;
                c.font = fontBold10;
                c.alignment = alignCenter;
                c.border = borderThin;
            });

            let startR = 8;
            let idx = 0;

            if (isAll) {
                // Parse dari UI Table HTML (Semua gabungan plant)
                $("#tbody-so-karantina tr").each(function () {
                    let tds = $(this).find("td");
                    if (tds.length > 1) {
                        let r = ws.getRow(startR + idx);
                        r.getCell(1).value = Number(tds.eq(0).text().trim());
                        r.getCell(2).value = tds.eq(1).text().trim();
                        r.getCell(3).value = tds.eq(2).text().trim();
                        r.getCell(4).value =
                            Number(tds.eq(3).text().replace(/\./g, "")) || 0;
                        r.getCell(5).value =
                            Number(tds.eq(4).text().replace(/\./g, "")) || 0;
                        r.getCell(6).value =
                            Number(tds.eq(5).text().replace(/\./g, "")) || 0;
                        r.getCell(7).value =
                            Number(tds.eq(6).text().replace(/\./g, "")) || 0;

                        r.getCell(1).alignment = alignCenter;
                        r.getCell(2).alignment = alignLeft;
                        r.getCell(3).alignment = alignLeft;
                        [4, 5, 6, 7].forEach((c) => {
                            r.getCell(c).alignment = alignRight;
                            r.getCell(c).numFmt = "#,##0";
                        });
                        for (let c = 1; c <= 7; c++) {
                            r.getCell(c).border = borderThin;
                            r.getCell(c).font = fontNormal10;
                        }
                        idx++;
                    }
                });
            } else {
                // Parse dari Data AJAX (Filter spesifik by Plant)
                let filteredData = (window.dataExportPlant || []).filter(
                    (d) => d.plant === plantCode,
                );
                filteredData.forEach((item) => {
                    let r = ws.getRow(startR + idx);
                    r.getCell(1).value = idx + 1;
                    r.getCell(2).value = item.item_code_desc;
                    r.getCell(3).value = item.description || "-";
                    r.getCell(4).value = Number(item.shift_1) || 0;
                    r.getCell(5).value = Number(item.shift_2) || 0;
                    r.getCell(6).value = Number(item.shift_3) || 0;
                    r.getCell(7).value = Number(item.total_bstb) || 0;

                    r.getCell(1).alignment = alignCenter;
                    r.getCell(2).alignment = alignLeft;
                    r.getCell(3).alignment = alignLeft;
                    [4, 5, 6, 7].forEach((c) => {
                        r.getCell(c).alignment = alignRight;
                        r.getCell(c).numFmt = "#,##0";
                    });
                    for (let c = 1; c <= 7; c++) {
                        r.getCell(c).border = borderThin;
                        r.getCell(c).font = fontNormal10;
                    }
                    idx++;
                });
            }

            let footR = startR + idx;
            ws.mergeCells(`A${footR}:C${footR}`);
            let fC = ws.getCell(`A${footR}`);
            fC.value = "GRAND TOTAL";
            fC.font = fontBold10;
            fC.alignment = alignCenter;

            ["D", "E", "F", "G"].forEach((col) => {
                let c = ws.getCell(`${col}${footR}`);
                c.value = {
                    formula: `SUM(${col}${startR}:${col}${footR - 1})`,
                };
                c.numFmt = "#,##0";
                c.font = fontBold10;
                c.alignment = alignRight;
            });

            for (let c = 1; c <= 7; c++)
                ws.getCell(footR, c).border = borderThin;
        }

        // =========================================================================
        // EKSEKUSI PEMBUATAN SEMUA SHEET
        // =========================================================================
        createRekapSheet("Rekap BSTB Per Shift Plant ALL", true);

        // Generate sheet tambahan sesuai Plant yang ada (B, H, I, T)
        let availablePlants = [
            ...new Set((window.dataExportPlant || []).map((d) => d.plant)),
        ];
        ["B", "H", "I", "T"].forEach((p) => {
            if (availablePlants.includes(p)) {
                // Nama sheet dibatasin max 31 karakter oleh Excel
                createRekapSheet(`Rekap BSTB Per Shift Plant ${p}`, false, p);
            }
        });

        // =========================================================================
        // DOWNLOAD FILE
        // =========================================================================
        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        let timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        link.href = url;
        link.download = `Report_SO_Karantina_${timestamp}.xlsx`;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        Swal.fire({
            title: "Export Excel Sukses! 🎉",
            text: "Berhasil membuat sheet per Plant dengan format rapi bro!",
            type: "success",
            confirmButtonColor: "#198754",
            timer: 3000,
        });
    } catch (err) {
        console.error(err);
        Swal.fire({
            title: "Export Gagal!",
            text: "Ada error: " + err.message,
            type: "error",
            confirmButtonColor: "#d33",
        });
    }
}

// ==========================================
// 🎯 MODAL DETAIL PLANT (ON CLICK CARD)
// ==========================================
function openPlantModal(plantCode) {
    // Set Nama Plant di Judul
    $("#modal-plant-name").text(plantCode);

    // Ambil data mentah export yang sudah disave di window
    let allData = window.dataExportPlant || [];

    // Filter khusus plant yang di-klik
    let plantData = allData.filter((d) => d.plant === plantCode);

    let tbody = $("#tbody-detail-plant");
    tbody.empty();

    // Variabel penampung resume
    let sumS1 = 0,
        sumS2 = 0,
        sumS3 = 0,
        sumTot = 0;
    let skuCount = plantData.length;

    if (skuCount > 0) {
        let html = "";
        $.each(plantData, function (index, item) {
            // Hitung summary
            let s1 = Number(item.shift_1) || 0;
            let s2 = Number(item.shift_2) || 0;
            let s3 = Number(item.shift_3) || 0;
            let tot = Number(item.total_bstb) || 0;

            sumS1 += s1;
            sumS2 += s2;
            sumS3 += s3;
            sumTot += tot;

            // Generate baris tabel
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="text-start fw-bold">${item.item_code_desc}</td>
                    <td class="text-start text-muted" style="font-size: 0.85rem;">${item.description || "-"}</td>
                    <td>${formatNumber(s1)}</td>
                    <td>${formatNumber(s2)}</td>
                    <td>${formatNumber(s3)}</td>
                    <td class="bg-warning bg-opacity-10 fw-bold text-dark">${formatNumber(tot)}</td>
                </tr>
            `;
        });
        tbody.html(html);
    } else {
        tbody.html(
            '<tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data produksi untuk Plant ini pada filter yang dipilih.</td></tr>',
        );
    }

    // Tulis data Resume ke Modal
    $("#modal-plant-sku").text(formatNumber(skuCount));
    $("#modal-plant-s1").text(formatNumber(sumS1));
    $("#modal-plant-s2").text(formatNumber(sumS2));
    $("#modal-plant-s3").text(formatNumber(sumS3));
    $("#modal-plant-total").text(formatNumber(sumTot));

    // Buka Modal pakai Bootstrap 5 API
    let modalEl = new bootstrap.Modal(
        document.getElementById("modalDetailPlant"),
    );
    modalEl.show();
}

// ==========================================
// 🎯 MODAL DETAIL SCAN (ON CLICK ROW TABLE)
// ==========================================
function openScanDetailModal(itemCode) {
    // Set text judul modal dengan nama item
    $("#modal-scan-item").text(itemCode);

    let tbody = $("#tbody-detail-scan");
    // Colspan diganti jadi 7
    tbody.html(
        '<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data scan...</td></tr>',
    );
    $("#tfoot-scan-detail-qty").text("0");

    // Buka Modal
    let modalEl = new bootstrap.Modal(
        document.getElementById("modalDetailScan"),
    );
    modalEl.show();

    // Tembak AJAX ke server buat ngambil detail scan
    $.ajax({
        url: "/so-karantina/scan-detail",
        type: "GET",
        data: { item_code: itemCode },
        dataType: "json",
        success: function (response) {
            let html = "";
            let totalQty = 0;

            if (response.data && response.data.length > 0) {
                $.each(response.data, function (index, item) {
                    let qty = Number(item.QtyStk) || 0;
                    totalQty += qty;

                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.opr || "-"}</td>
                            <td class="text-start">${item.nama_opr || "-"}</td> <td>${item.NoDoc || "-"}</td>
                            <td class="text-start fw-bold">${item.item_code_desc}</td>
                            <td class="text-start text-muted" style="font-size: 0.85rem;">${item.description || "-"}</td>
                            <td class="bg-primary bg-opacity-10 fw-bold">${formatNumber(qty)}</td>
                        </tr>
                    `;
                });
            } else {
                html =
                    '<tr><td colspan="7" class="text-center py-4 text-danger">Belum ada data hasil scan untuk item ini.</td></tr>';
            }

            tbody.html(html);
            $("#tfoot-scan-detail-qty").text(formatNumber(totalQty));
        },
        error: function (xhr, status, error) {
            // Tarik pesan error asli yang dikirim dari Backend (Controller)
            let errMsg =
                xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : error;

            console.error("Error fetching scan detail:", errMsg);

            // Tampilkan error langsung ke layar user biar kelihatan masalahnya
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger fw-bold">
                        <i data-lucide="alert-triangle" size="20" class="me-2"></i>
                        Gagal memuat data dari server:<br>
                        <small class="text-muted fw-normal">${errMsg}</small>
                    </td>
                </tr>
            `);

            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        },
    });
}
