let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();
let selectedDate = null;
let myChart = null;

// Object untuk menampung pilihan slicer
let activeSlicers = {
    grades: "",
    products: "",
    types: "",
    brands: "",
    categories: "",
};

function renderCalendar() {
    const grid = document.getElementById("calendarGrid");
    const monthTitle = document.getElementById("calMonthTitle");
    const months = [
        "JANUARI",
        "FEBRUARI",
        "MARET",
        "APRIL",
        "MEI",
        "JUNI",
        "JULI",
        "AGUSTUS",
        "SEPTEMBER",
        "OKTOBER",
        "NOVEMBER",
        "DESEMBER",
    ];

    if (!grid || !monthTitle) return;

    monthTitle.innerText = `${months[currentMonth]} ${currentYear}`;
    grid.innerHTML = "";

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    let offset = firstDay === 0 ? 6 : firstDay - 1;

    for (let i = 0; i < offset; i++)
        grid.innerHTML += `<div class="col-1-7"></div>`;

    for (let day = 1; day <= daysInMonth; day++) {
        const d = String(day).padStart(2, "0");
        const m = String(currentMonth + 1).padStart(2, "0");
        const dateStr = `${currentYear}-${m}-${d}`;

        const hasOracle = (window.oracleDates || []).includes(dateStr);
        const hasBarcode = (window.barcodeDates || []).includes(dateStr);
        const isActive = selectedDate === dateStr ? "active" : "";

        grid.innerHTML += `
            <div class="col-1-7">
                <button onclick="selectDate('${dateStr}')" class="btn-cal ${isActive}" id="date-${dateStr}">
                    <span>${day}</span>
                    <div class="dot-container">
                        ${hasBarcode ? '<div class="dot-barcode"></div>' : ""}
                        ${hasOracle ? '<div class="dot-oracle"></div>' : ""}
                    </div>
                </button>
            </div>`;
    }
    if (window.lucide) lucide.createIcons();
}

// FUNGSI PILIH TANGGAL (REVISI AMAN)
window.selectDate = function (date) {
    selectedDate = date;

    // Tambahkan pengecekan ini biar nggak error pas elemennya dihapus
    const badge = document.getElementById("selectedDateBadge");
    if (badge) {
        badge.innerText = date;
        badge.classList.replace("bg-primary", "bg-success");
    }

    renderCalendar();
    window.applyFilters(); // Sekarang baris ini pasti terpanggil
};

// FUNGSI TOGGLE SLICER (LANGSUNG EKSEKUSI JIKA TANGGAL SUDAH ADA)
window.toggleSlicer = function (element, group, value) {
    // 1. Visual update: Cari SEMUA slicer-item di card kiri dan matikan class active-nya
    // Biar kalau klik OE, yang di OK mati.
    document.querySelectorAll(".slicer-item").forEach((item) => {
        item.classList.remove("active");
    });

    // 2. Aktifkan yang baru saja diklik
    element.classList.add("active");

    // 3. State update
    activeSlicers[group] = value;

    // 4. Eksekusi filter (Grafik bakal berubah sekarang!)
    window.applyFilters();
};

window.changeMonth = function (step) {
    currentMonth += step;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    } else if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar();
    window.applyFilters(); // <--- GASS UPDATE GRAFIK PAS PINDAH BULAN
};

window.applyFilters = function () {
    const loader = document.getElementById("chart-loader");
    const placeholder = document.getElementById("table-render-area");
    const chartArea = document.getElementById("chart-container");
    const additionalArea = document.getElementById("additional-info-area");

    // Tampilkan loader, sembunyikan placeholder "Ready to Load"
    if (loader) loader.classList.remove("d-none");
    if (placeholder) placeholder.classList.add("d-none");

    const params = new URLSearchParams({
        month: currentMonth + 1,
        year: currentYear,
        date: selectedDate || "",
        patterns: activeSlicers.patterns || "",
        grades: activeSlicers.grades || "",
        products: activeSlicers.products || "",
        types: activeSlicers.types,
        brands: activeSlicers.brands,
        categories: activeSlicers.categories,
    }).toString();

    fetch(`/oracle-barcode/chart-data?${params}`, {
        method: "GET",
        headers: { "X-Requested-With": "XMLHttpRequest" },
    })
        .then((response) => response.json())
        .then((res) => {
            if (loader) loader.classList.add("d-none");

            // --- KUNCI: MUNCULIN CONTAINER DULUAN ---
            if (chartArea) chartArea.classList.remove("d-none");
            if (additionalArea) additionalArea.classList.remove("d-none");

            // Render Grafik (Akan jadi bulanan kalau selectedDate null)
            renderComparisonChart(res);

            // Render Tabel (Akan muncul teks "Pilih Tanggal" kalau selectedDate null)
            renderSummaryTable(res);

            // Paksa resize biar gak menciut
            setTimeout(() => {
                if (myChart) myChart.resize();
            }, 300);
        })
        .catch((err) => {
            if (loader) loader.classList.add("d-none");
            console.error("Fetch Error:", err);
        });
};

function renderSummaryTable(res) {
    const container = document.getElementById("additional-info-area");

    // 1. CEK DATA (Biar gak error kalau res kosong)
    if (!res || !res.summary_table) return;

    // 2. LOGIC: Jika belum pilih tanggal, tampilkan instruksi
    if (!selectedDate) {
        container.innerHTML = `
            <div class="card border shadow-sm" style="border-radius: 8px;">
                <div class="card-body p-5 text-center text-muted">
                    <i data-lucide="calendar-check-2" style="width: 40px; height: 40px;" class="mb-2 opacity-20"></i>
                    <h6 class="fw-bold small text-uppercase" style="letter-spacing:1px;">Pilih Tanggal Dahulu</h6>
                    <p class="small mb-0 opacity-75">Tabel summary variance akan muncul otomatis setelah Anda memilih salah satu tanggal transaksi di Control Panel.</p>
                </div>
            </div>`;
        if (window.lucide) lucide.createIcons();
        return;
    }

    // 3. AMBIL DATA DARI RESPONSE SERVER
    const summaryData = res.summary_table;
    const patternsOE = res.patterns_oe || [];
    const patternsOK = res.patterns_ok || [];

    // 4. PROSES FORMAT TANGGAL INDONESIA
    const monthsIndo = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];
    const parts = selectedDate.split("-");
    const formattedDate = `${parts[2]} ${monthsIndo[parseInt(parts[1]) - 1]} ${parts[0]}`;

    // 5. HELPER AMBIL DATA
    const getData = (p) =>
        summaryData.find((item) => item.pattern === p) || {
            barcode_total: 0,
            oracle_total: 0,
        };

    // 6. RENDER HTML
    container.innerHTML = `
    <div class="card border shadow-sm" style="border-radius: 8px;">
        <div class="card-header bg-light py-1 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold small text-uppercase mb-0" style="font-size: 11px; color: #132541;">
                <i data-lucide="table-properties" class="me-1" style="width:14px;"></i>
                Summary Variance: ${formattedDate}
            </h6>
            <span class="badge rounded-pill bg-warning blink-me shadow-sm px-2 py-1 text-dark fw-bold" style="color: #000 !important;">
                <i class="fa-solid fa-hand-pointer me-1"></i> Klik baris untuk melihat detail data item
            </span>
        </div>
        <div class="card-body p-2 overflow-auto custom-scroll" style="max-height: 400px;">
            <div class="row g-2">
                <div class="col-md-6">
                    <p class="fw-bold text-center mb-1" style="font-size: 10px; color: #191bdf;">GRADE OE</p>
                    ${generateTableHTML(1, patternsOE, getData, "OE")}
                </div>
                <div class="col-md-6">
                    <p class="fw-bold text-center mb-1" style="font-size: 10px; color: #fe6807;">GRADE OK</p>
                    ${generateTableHTML(patternsOE.length + 1, patternsOK, getData, "OK")}
                </div>
            </div>
        </div>
    </div>`;

    if (window.lucide) lucide.createIcons();
}

function generateTableHTML(startNo, patternList, getDataFn, title) {
    let rows = "";
    let visibleIndex = 1;

    patternList.forEach((p, i) => {
        const d = getDataFn(p);
        const barcode = parseInt(d.barcode_total) || 0;
        const oracle = parseInt(d.oracle_total) || 0;
        const variance = barcode - oracle;

        // AMBIL DATA SKU DARI CONTROLLER
        const skuMinus = parseInt(d.sku_minus) || 0;
        const skuPlus = parseInt(d.sku_plus) || 0;

        if (barcode === 0 && oracle === 0 && variance === 0) return;

        const varTextColor =
            variance < 0 ? "text-danger" : variance > 0 ? "text-primary" : "";

        rows += `
            <tr onclick="showDetail('${p}')" style="font-size: 10px; cursor:pointer;">
                <td class="text-center text-muted fw-bold">${visibleIndex++}</td>
                <td class="fw-bold" style="color: #333;">${p}</td>
                <td class="text-end fw-bold" style="color: #132541;">${barcode.toLocaleString("id-ID")}</td>
                <td class="text-end fw-bold" style="color: #9a7d0a;">${oracle.toLocaleString("id-ID")}</td>
                <td class="text-end fw-black ${varTextColor}">${variance.toLocaleString("id-ID")}</td>
                <td class="text-center fw-bold text-danger bg-light">${skuMinus}</td>
                <td class="text-center fw-bold text-primary bg-light">${skuPlus}</td>
            </tr>`;
    });

    return `
        <table class="table table-sm table-bordered mb-0 table-hover-custom">
            <thead class="text-center text-white" style="font-size: 9px; text-transform: uppercase;">
                <tr>
                    <th rowspan="2" style="background-color: #6c757d; vertical-align: middle;">No</th>
                    <th rowspan="2" style="background-color: #6c757d; vertical-align: middle;">Pattern ${title}</th>
                    <th rowspan="2" style="background-color: #132541; vertical-align: middle;">BC</th>
                    <th rowspan="2" style="background-color: #C8A96E; vertical-align: middle; color:#000;">ORA</th>
                    <th colspan="3" style="background-color: #DC143C;">SUMMARY VARIANCE</th>
                </tr>
                <tr>
                    <th style="background-color: #b91234;">PCS</th>
                    <th style="background-color: #b91234;">SKU (-)</th>
                    <th style="background-color: #b91234;">SKU (+)</th>
                </tr>
            </thead>
            <tbody class="align-middle" style="background-color: #ffffff;">${rows}</tbody>
        </table>`;
}

let dataForExport = {
    pattern: "",
    resume: [],
    breakdown: [],
};

// --- FUNGSI TAMPIL DETAIL PATTERN ---
window.showDetail = function (pattern) {
    const title = document.getElementById("modalTitlePattern");
    const statsArea = document.getElementById("sku-stats");
    const contentArea = document.getElementById("modalDetailContent");

    title.innerText = "LOADING...";
    statsArea.innerHTML = "";
    contentArea.innerHTML = `
        <div class="d-flex flex-column justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <span class="fw-bold">Sedang memproses data pattern ${pattern}...</span>
        </div>`;

    const modalElement = document.getElementById("modalDetailPattern");
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    modal.show();

    const params = new URLSearchParams({
        pattern: pattern,
        date: selectedDate || "",
        month: currentMonth + 1,
        year: currentYear,
    }).toString();

    fetch(`/oracle-barcode/detail-pattern?${params}`)
        .then((res) => res.json())
        .then((res) => {
            title.innerText = res.pattern;
            dataForExport.pattern = res.pattern;
            dataForExport.resume = res.details; // Simpan ke global buat Excel

            let skuTotal = 0,
                skuPlus = 0,
                skuMinus = 0;
            let minusRows = "",
                plusRows = "";

            res.details.forEach((item) => {
                const bc = parseInt(item.barcode_total) || 0;
                const ora = parseInt(item.oracle_total) || 0;
                const v = bc - ora;

                if (bc === 0 && ora === 0 && v === 0) return;

                const rowHtml = `
                    <tr onclick="showDeepDetail('${item.item_code_desc}')" style="cursor:pointer; font-size: 11px;">
                        <td class="text-center text-muted">${v < 0 ? skuMinus + 1 : skuPlus + 1}</td>
                        <td class="fw-bold text-dark">${item.item_code_desc}</td>
                        <td class="text-truncate" style="max-width: 250px;" title="${item.description}">${item.description}</td>
                        <td class="text-end fw-bold text-primary">${bc.toLocaleString("id-ID")}</td>
                        <td class="text-end fw-bold text-muted">${ora.toLocaleString("id-ID")}</td>
                        <td class="text-end fw-black ${v < 0 ? "text-danger" : "text-primary"}">${v.toLocaleString("id-ID")}</td>
                    </tr>`;

                if (v < 0) {
                    minusRows += rowHtml;
                    skuMinus++;
                } else if (v > 0) {
                    plusRows += rowHtml;
                    skuPlus++;
                }
                skuTotal++;
            });

            // 1. RENDER STATS & TOMBOL EXCEL DI HEADER
            statsArea.innerHTML = `
                <button onclick="runExportResumeOnly('${res.pattern}')" class="btn btn-success btn-xs fw-bold me-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i> EXPORT EXCEL
                </button>
                <span class="badge bg-light text-dark border small shadow-sm">SKU: ${skuTotal}</span>
                <span class="badge bg-danger small shadow-sm">- ${skuMinus} SKU</span>
                <span class="badge bg-primary small shadow-sm">+ ${skuPlus} SKU</span>
            `;

            // 2. RENDER LAYOUT SPLIT DI BODY
            contentArea.innerHTML = `
                <div class="row g-3 h-100">
                    <div class="col-md-6 h-100 d-flex flex-column">
                        <div class="card border-danger shadow-sm flex-grow-1 overflow-hidden">
                            <div class="card-header bg-danger text-white py-1 small fw-bold">DATA MINUS (-) : ${skuMinus} SKU</div>
                            <div class="table-responsive custom-scroll flex-grow-1" style="overflow-y: auto;">
                                <table class="table table-sm table-hover table-striped mb-0">
                                    <thead class="bg-white sticky-top shadow-sm">
                                        <tr class="text-center" style="font-size: 10px;">
                                            <th>NO</th><th>ITEM CODE</th><th>DESCRIPTION</th><th>BC</th><th>ORA</th><th>VAR</th>
                                        </tr>
                                    </thead>
                                    <tbody>${minusRows || '<tr><td colspan="6" class="text-center py-5">Nggak ada data minus bro</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 h-100 d-flex flex-column">
                        <div class="card border-primary shadow-sm flex-grow-1 overflow-hidden">
                            <div class="card-header bg-primary text-white py-1 small fw-bold">DATA PLUS (+) : ${skuPlus} SKU</div>
                            <div class="table-responsive custom-scroll flex-grow-1" style="overflow-y: auto;">
                                <table class="table table-sm table-hover table-striped mb-0">
                                    <thead class="bg-white sticky-top shadow-sm">
                                        <tr class="text-center" style="font-size: 10px;">
                                            <th>NO</th><th>ITEM CODE</th><th>DESCRIPTION</th><th>BC</th><th>ORA</th><th>VAR</th>
                                        </tr>
                                    </thead>
                                    <tbody>${plusRows || '<tr><td colspan="6" class="text-center py-5">Nggak ada data plus bro</td></tr>'}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>`;

            if (window.lucide) lucide.createIcons();
        });
};

// --- FUNGSI EXCEL MODAL DETAIL PATTERN ---
async function runExportResumeOnly(patternName) {
    const ExcelLib =
        window.ExcelJS || (typeof ExcelJS !== "undefined" ? ExcelJS : null);
    if (!ExcelLib) return alert("Library ExcelJS tidak ditemukan!");

    try {
        const workbook = new ExcelLib.Workbook();
        const worksheet = workbook.addWorksheet("Resume Audit");
        const dateTitle = selectedDate
            ? selectedDate
            : `Bulan ${currentMonth + 1} - ${currentYear}`;

        // 1. HEADER LAPORAN (Merge ke tengah)
        worksheet.mergeCells("A1:L1");
        const mainTitle = worksheet.getCell("A1");
        mainTitle.value = "LAPORAN RESUME AUDIT STOK PER ITEM (SPLIT VIEW)";
        mainTitle.font = { size: 14, bold: true };
        mainTitle.alignment = { horizontal: "center" };

        worksheet.addRow(["Pattern", ": " + patternName]);
        worksheet.addRow(["Tanggal Data", ": " + dateTitle]);
        worksheet.addRow([]); // Baris kosong

        // 2. HEADER TABEL (KIRI: MINUS | KANAN: PLUS)
        // Kolom A-F (Minus) | Kolom G (Spacer) | Kolom H-M (Plus)
        const headerRow = worksheet.addRow([
            "NO",
            "ITEM CODE",
            "DESCRIPTION",
            "BC",
            "ORA",
            "VAR", // KIRI
            "", // SPACER KOLOM G
            "NO",
            "ITEM CODE",
            "DESCRIPTION",
            "BC",
            "ORA",
            "VAR", // KANAN
        ]);

        // Styling Header
        headerRow.eachCell((cell, colNumber) => {
            if (colNumber >= 1 && colNumber <= 6) {
                // Warna Merah untuk Minus
                cell.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: "FFFF0000" },
                };
                cell.font = { color: { argb: "FFFFFFFF" }, bold: true };
            } else if (colNumber >= 8 && colNumber <= 13) {
                // Warna Biru untuk Plus
                cell.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: "FF191BDF" },
                };
                cell.font = { color: { argb: "FFFFFFFF" }, bold: true };
            }
            if (colNumber !== 7) {
                cell.border = {
                    top: { style: "thin" },
                    left: { style: "thin" },
                    bottom: { style: "thin" },
                    right: { style: "thin" },
                };
                cell.alignment = { horizontal: "center" };
            }
        });

        // 3. PISAHKAN DATA
        const minusData = dataForExport.resume.filter(
            (d) => parseInt(d.barcode_total) - parseInt(d.oracle_total) < 0,
        );
        const plusData = dataForExport.resume.filter(
            (d) => parseInt(d.barcode_total) - parseInt(d.oracle_total) > 0,
        );

        const maxRows = Math.max(minusData.length, plusData.length);

        for (let i = 0; i < maxRows; i++) {
            const m = minusData[i] || null;
            const p = plusData[i] || null;

            const row = worksheet.addRow([
                // DATA MINUS (A-F)
                m ? i + 1 : "",
                m ? m.item_code_desc : "",
                m ? m.description : "",
                m ? parseInt(m.barcode_total) : "",
                m ? parseInt(m.oracle_total) : "",
                m ? parseInt(m.barcode_total) - parseInt(m.oracle_total) : "",

                "", // SPACER (G)

                // DATA PLUS (H-M)
                p ? i + 1 : "",
                p ? p.item_code_desc : "",
                p ? p.description : "",
                p ? parseInt(p.barcode_total) : "",
                p ? parseInt(p.oracle_total) : "",
                p ? parseInt(p.barcode_total) - parseInt(p.oracle_total) : "",
            ]);

            // Styling Border per cell
            row.eachCell((cell, colNumber) => {
                if (colNumber !== 7 && cell.value !== "") {
                    cell.border = {
                        top: { style: "thin" },
                        left: { style: "thin" },
                        bottom: { style: "thin" },
                        right: { style: "thin" },
                    };
                    if (colNumber === 6)
                        cell.font = { color: { argb: "FFFF0000" }, bold: true }; // Var Minus Merah
                    if (colNumber === 13)
                        cell.font = { color: { argb: "FF191BDF" }, bold: true }; // Var Plus Biru
                }
            });
        }

        // 4. ATUR LEBAR KOLOM
        const colWidths = [5, 20, 35, 8, 8, 8, 3, 5, 20, 35, 8, 8, 8];
        colWidths.forEach((w, i) => {
            worksheet.getColumn(i + 1).width = w;
        });

        // 5. DOWNLOAD
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        saveAs(
            blob,
            `Resume_Split_Audit_${patternName.replace(/ /g, "_")}.xlsx`,
        );
    } catch (err) {
        console.error(err);
        alert("Gagal export split excel: " + err.message);
    }
}

window.showDeepDetail = function (itemCodeDesc) {
    const tableBody = document.getElementById("deepDetailTableBody");
    const titleElement = document.getElementById("deepDetailTitle");
    const exportBtn = document.getElementById("btnExportDeepExcel"); // Pastikan ID ini ada di Blade

    titleElement.innerText = itemCodeDesc;
    tableBody.innerHTML =
        '<tr><td colspan="6" class="text-center py-3">Loading...</td></tr>';

    const modal = new bootstrap.Modal(
        document.getElementById("modalDeepDetail"),
    );
    modal.show();

    const params = new URLSearchParams({
        item_code_desc: itemCodeDesc,
        date: selectedDate || "",
        month: currentMonth + 1,
        year: currentYear,
    }).toString();

    fetch(`/oracle-barcode/deep-detail?${params}`)
        .then((res) => res.json())
        .then((data) => {
            dataForExport.breakdown = data; // Simpan data breakdown lokasi
            tableBody.innerHTML = "";

            data.forEach((row, i) => {
                tableBody.innerHTML += `
                <tr>
                    <td class="text-center">${i + 1}</td>
                    <td>${row.loc_code}</td>
                    <td>${row.rack_code}</td>
                    <td>${itemCodeDesc}</td>
                    <td>${row.description || "-"}</td>
                    <td class="text-end fw-bold text-success">${parseInt(row.qty).toLocaleString("id-ID")}</td>
                </tr>`;
            });

            // Set klik tombol export - Pakai pengecekan null biar gak error console
            if (exportBtn) {
                exportBtn.onclick = () => runExportFinal(itemCodeDesc);
            }
        });
};

async function runExportFinal(itemCodeDesc) {
    // Pastikan library lu terdeteksi (Ganti 'ExcelJS' dengan objek utama library lu jika berbeda)
    const ExcelLib =
        window.ExcelJS || (typeof ExcelJS !== "undefined" ? ExcelJS : null);

    if (!ExcelLib) {
        alert(
            "Library excel.min.js tidak mendukung styling atau tidak terload. Coba cek console!",
        );
        return;
    }

    try {
        const workbook = new ExcelLib.Workbook();
        const worksheet = workbook.addWorksheet("Detail Barcode");
        const dateTitle = selectedDate
            ? selectedDate
            : `Bulan ${currentMonth + 1} - ${currentYear}`;

        // 1. INFO HEADER (Tanpa Border)
        worksheet.addRow(["LAPORAN DETAIL DATA BARCODE (BREAKDOWN)"]);
        worksheet.addRow(["PT. GAJAH TUNGGAL Tbk. - GUDANG BAN B"]);
        worksheet.addRow(["Item Code Desc", ": " + itemCodeDesc]);
        worksheet.addRow(["Tanggal Data", ": " + dateTitle]);
        worksheet.addRow([]); // Baris Kosong

        // 2. HEADER TABEL (Kita kasih warna dan border)
        const headerRow = worksheet.addRow([
            "NO",
            "LOC CODE",
            "RACK CODE",
            "ITEM CODE DESC",
            "DESCRIPTION",
            "QTY",
        ]);

        // Styling Header
        headerRow.eachCell((cell) => {
            cell.fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "FF132541" }, // Biru gelap (Warna kebanggaan lu)
            };
            cell.font = { color: { argb: "FFFFFFFF" }, bold: true };
            cell.border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };
            cell.alignment = { horizontal: "center" };
        });

        // 3. MASUKKAN DATA (Sambil kasih border)
        if (dataForExport.breakdown && dataForExport.breakdown.length > 0) {
            dataForExport.breakdown.forEach((b, i) => {
                const row = worksheet.addRow([
                    i + 1,
                    b.loc_code,
                    b.rack_code,
                    itemCodeDesc,
                    b.description || "",
                    parseInt(b.qty) || 0,
                ]);

                // Kasih Border di tiap cell data
                row.eachCell((cell) => {
                    cell.border = {
                        top: { style: "thin" },
                        left: { style: "thin" },
                        bottom: { style: "thin" },
                        right: { style: "thin" },
                    };
                });
            });
        }

        // 4. SET LEBAR KOLOM
        worksheet.getColumn(1).width = 5;
        worksheet.getColumn(2).width = 15;
        worksheet.getColumn(3).width = 15;
        worksheet.getColumn(4).width = 25;
        worksheet.getColumn(5).width = 50;
        worksheet.getColumn(6).width = 10;

        // 5. DOWNLOAD FILE
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const url = window.URL.createObjectURL(blob);
        const anchor = document.createElement("a");
        anchor.href = url;
        anchor.download = `Audit_Barcode_${itemCodeDesc.replace(/[^a-z0-9]/gi, "_")}.xlsx`;
        anchor.click();
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error("Gagal Styling Excel:", err);
        alert(
            "Terjadi kesalahan saat memproses styling. Pastikan library excel.min.js sudah benar!",
        );
    }
}

// Grafik
function renderComparisonChart(data) {
    const chartDom = document.getElementById("main-chart");
    if (myChart) myChart.dispose();
    myChart = echarts.init(chartDom);

    // 1. SINKRONISASI BACKGROUND: Cari nilai tertinggi per tanggal agar bingkai merah menaungi keduanya
    const varianceBackgroundData = data.barcode.map((val, index) => {
        const oracleVal = data.oracle[index];
        const diff = val - oracleVal;

        // Jika selisihnya 0, set tinggi background jadi 0 (bingkai hilang total)
        if (diff === 0) return 0;

        // Jika ada selisih, baru cari nilai tertinggi untuk menentukan tinggi bingkai
        const maxVal = Math.max(val, oracleVal);
        return Math.ceil(maxVal * 1.15);
    });

    const option = {
        title: {
            text: `Monthly Stock Integrity: ${data.month_name} ${data.year}`,
            left: "center",
            textStyle: { fontSize: 14, fontWeight: "900", color: "#132541" },
            top: -5,
        },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
            backgroundColor: "rgba(255, 255, 255, 0.9)",
            formatter: function (params) {
                let res = `<b>Tanggal ${params[0].name}</b><br/>`;
                params.forEach((item) => {
                    if (
                        item.seriesName !== "Variance Background" &&
                        item.seriesName !== "Actual Variance"
                    ) {
                        res += `${item.marker} ${item.seriesName}: ${item.value.toLocaleString("id-ID")}<br/>`;
                    }
                });
                // Hitung variance harian untuk tooltip
                const idx = params[0].dataIndex;
                const v = data.barcode[idx] - data.oracle[idx];
                res += `<span style="color:#DC143C;">●</span> Variance: ${v.toLocaleString("id-ID")}`;
                return res;
            },
        },
        legend: {
            data: ["Barcode", "Oracle", "Actual Variance"],
            top: 10,
        },
        grid: {
            left: "-5%",
            right: "1%",
            bottom: "5%",
            top: "80px",
            containLabel: true,
        },
        xAxis: {
            type: "category",
            data: data.labels,
            axisLabel: { fontSize: 10, fontWeight: "800", interval: 0 },
            axisLine: { lineStyle: { color: "#ddd" } },
            axisTick: { show: false },
            splitLine: {
                show: true,
                lineStyle: {
                    color: "#000", // Warna garis pembatas (abu-abu muda)
                    type: "solid", // Jenis garis (bisa 'dashed' atau 'dotted')
                },
            },
        },
        yAxis: {
            type: "value",
            show: false,
            splitLine: { show: false },
        },
        series: [
            // --- 1. BINGKAI MERAH (BACKDROP) ---
            {
                name: "Variance Background",
                type: "pictorialBar",
                symbol: "rect",
                itemStyle: { color: "#DC143C" },
                barWidth: "85%",
                data: varianceBackgroundData,
                z: 1,
                animation: false,
                label: {
                    show: true,
                    position: "top",
                    align: "left",
                    verticalAlign: "middle",
                    distance: 5,
                    rotate: 90,
                    fontSize: 11,
                    fontWeight: "bold",
                    color: "#DC143C",
                    // FORMULA: BARCODE - ORACLE
                    formatter: function (params) {
                        const idx = params.dataIndex;
                        const diff = data.barcode[idx] - data.oracle[idx];
                        return diff !== 0 ? diff.toLocaleString("id-ID") : "";
                    },
                },
                silent: true,
            },
            // --- 2. BARCODE (KIRI) ---
            {
                name: "Barcode",
                type: "bar",
                data: data.barcode,
                barWidth: "35%",
                barGap: "-10%", // Tarik paksa ke tengah bingkai
                itemStyle: { color: "#132541" },
                z: 2,
                label: {
                    show: true,
                    position: "insideBottom",
                    distance: 10,
                    rotate: 90,
                    align: "left",
                    verticalAlign: "middle",
                    fontSize: 9,
                    color: "#fff",
                    formatter: (params) =>
                        params.value > 0
                            ? params.value.toLocaleString("id-ID")
                            : "",
                },
            },
            // --- 3. ORACLE (KANAN) ---
            {
                name: "Oracle",
                type: "bar",
                data: data.oracle,
                barWidth: "35%",
                itemStyle: { color: "#C8A96E" },
                z: 2,
                label: {
                    show: true,
                    position: "insideBottom",
                    distance: 10,
                    rotate: 90,
                    align: "left",
                    verticalAlign: "middle",
                    fontSize: 9,
                    color: "#000",
                    formatter: (params) =>
                        params.value > 0
                            ? params.value.toLocaleString("id-ID")
                            : "",
                },
            },
            // --- 4. LEGEND DUMMY ---
            {
                name: "Actual Variance",
                type: "bar",
                data: [],
                itemStyle: { color: "#DC143C" },
            },
        ],
    };
    myChart.setOption(option);
    myChart.resize();
}

// Tambahkan inisialisasi resize
window.addEventListener("resize", function () {
    if (myChart) myChart.resize();
});

// Inisialisasi saat pertama kali buka halaman
document.addEventListener("DOMContentLoaded", function () {
    renderCalendar();
    selectedDate = null;
    window.applyFilters(); // Langsung load grafik bulan berjalan
});
