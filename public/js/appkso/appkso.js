/**
 * Script APPKSO - Client Side Logic (Premium Search)
 */
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const tableBody = document.getElementById("tableBody");
    const rows = tableBody ? tableBody.getElementsByTagName("tr") : [];

    // =============================================
    // FUNGSI SEARCH
    // =============================================
    if (searchInput) {
        searchInput.addEventListener("keyup", function (e) {
            const searchText = e.target.value.toLowerCase();
            Array.from(rows).forEach((row) => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchText) ? "" : "none";
            });
        });
    }

    // =============================================
    // AUTO REFRESH LOOP + COUNTDOWN 10 DETIK
    // =============================================
    const btnRefresh = document.getElementById("btnRefreshData");

    if (btnRefresh) {
        let countdownInterval = null;
        let isAutoRefresh = false;

        // Helper: reset tombol ke default (OFF)
        function resetBtnRefresh() {
            btnRefresh.classList.remove("btn-danger");
            btnRefresh.classList.add("btn-warning");
            btnRefresh.innerHTML = `<i data-lucide="refresh-cw" class="me-1" style="width:12px;height:12px;"></i> Refresh`;
            if (window.lucide) lucide.createIcons({ nodes: [btnRefresh] });
        }

        // Helper: fetch data
        function doRefresh() {
            btnRefresh.innerHTML = `<i data-lucide="refresh-cw" class="me-1" style="width:12px;height:12px;animation:spin 1s linear infinite;"></i> Fetching...`;
            if (window.lucide) lucide.createIcons({ nodes: [btnRefresh] });

            const soSelect = document.getElementById("so_select");
            const soName = soSelect ? soSelect.value : "";
            const url = new URL(window.location.href);
            url.searchParams.set("so_name", soName);

            fetch(url.toString(), {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
                .then((res) => res.text())
                .then((html) => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");

                    // Update tableBody
                    const newTableBody = doc.getElementById("tableBody");
                    if (newTableBody && tableBody) {
                        tableBody.innerHTML = newTableBody.innerHTML;
                    }

                    // Update Resume PIC
                    const allMainTables =
                        document.querySelectorAll("#mainTable tbody");
                    const newMainTables =
                        doc.querySelectorAll("#mainTable tbody");
                    allMainTables.forEach((tb, i) => {
                        if (newMainTables[i])
                            tb.innerHTML = newMainTables[i].innerHTML;
                    });

                    // Update stat boxes
                    const statBoxes = document.querySelectorAll(
                        ".footer-stat-box span, .fw-black",
                    );
                    const newStatBoxes = doc.querySelectorAll(
                        ".footer-stat-box span, .fw-black",
                    );
                    statBoxes.forEach((el, i) => {
                        if (newStatBoxes[i])
                            el.textContent = newStatBoxes[i].textContent;
                    });

                    if (window.Swal) {
                        Swal.fire({
                            type: "success",
                            title: "Data Diperbarui",
                            text: "Auto refresh berhasil.",
                            timer: 1000,
                            showConfirmButton: false,
                            toast: true,
                            position: "top-end",
                        });
                    }
                })
                .catch((err) => {
                    console.error("Refresh gagal:", err);
                })
                .finally(() => {
                    // Kalau masih mode auto, langsung mulai countdown lagi
                    if (isAutoRefresh) {
                        startCountdown();
                    } else {
                        resetBtnRefresh();
                    }
                });
        }

        // Helper: mulai countdown 10 detik lalu fetch
        function startCountdown() {
            let sisa = 10;
            btnRefresh.classList.remove("btn-warning");
            btnRefresh.classList.add("btn-danger");
            btnRefresh.innerHTML = `<i data-lucide="stop-circle" class="me-1" style="width:12px;height:12px;"></i> Stop (${sisa}s)`;
            if (window.lucide) lucide.createIcons({ nodes: [btnRefresh] });

            countdownInterval = setInterval(() => {
                sisa--;

                if (sisa > 0) {
                    btnRefresh.innerHTML = `<i data-lucide="stop-circle" class="me-1" style="width:12px;height:12px;"></i> Stop (${sisa}s)`;
                    if (window.lucide)
                        lucide.createIcons({ nodes: [btnRefresh] });
                } else {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                    doRefresh(); // fetch → kalau masih ON, startCountdown() lagi
                }
            }, 1000);
        }

        // Klik tombol: toggle ON/OFF
        btnRefresh.addEventListener("click", function () {
            if (isAutoRefresh) {
                // STOP
                isAutoRefresh = false;
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                    countdownInterval = null;
                }
                resetBtnRefresh();
            } else {
                // START
                isAutoRefresh = true;
                startCountdown();
            }
        });
    }

    // =============================================
    // CSS SPIN
    // =============================================
    const style = document.createElement("style");
    style.textContent = `@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }`;
    document.head.appendChild(style);

    // =============================================
    // INIT LUCIDE ICONS
    // =============================================
    if (window.lucide) {
        lucide.createIcons();
    }

    // =============================================
    // MODAL UPLOAD ORACLE
    // =============================================
    const selectOpr = document.getElementById("selectOprUpload");
    const btnDownload = document.getElementById("btnDownloadXlsm");
    const previewBox = document.getElementById("previewInfoUpload");

    if (selectOpr) {
        selectOpr.addEventListener("change", function () {
            const selected = this.options[this.selectedIndex];
            const opr = selected.value;
            const oprName = selected.getAttribute("data-name") || "-";

            if (opr) {
                document.getElementById("previewOprName").textContent =
                    oprName.toUpperCase();
                document.getElementById("previewOprCode").textContent = opr;
                previewBox.classList.remove("d-none");
                btnDownload.classList.remove("disabled");
            } else {
                previewBox.classList.add("d-none");
                btnDownload.classList.add("disabled");
            }
        });
    }

    // ==========================================
    // 🎯 EXPORT EXCEL MULTI-SHEET (ExcelJS)
    // ==========================================
    function formatTglExport(dateStr) {
        if (!dateStr || !dateStr.includes("-")) return dateStr || "-";
        let [y, m, d] = dateStr.split("-");
        return `${d}-${m}-${y.slice(-2)}`;
    }

    function sanitizeSheetName(name) {
        return String(name)
            .replace(/[:\\\/\?\*\[\]]/g, "")
            .substring(0, 31)
            .trim();
    }

    function thinBorder() {
        return {
            top: { style: "thin" },
            left: { style: "thin" },
            bottom: { style: "thin" },
            right: { style: "thin" },
        };
    }

    function mediumBorder() {
        return {
            top: { style: "medium" },
            left: { style: "medium" },
            bottom: { style: "medium" },
            right: { style: "medium" },
        };
    }

    // ==========================================
    // BUKA MODAL EXPORT EXCEL
    // ==========================================
    $(document)
        .off("click", "#btn-export-excel-appkso")
        .on("click", "#btn-export-excel-appkso", function () {
            let targetSo = $("#so_select").val();
            if (!targetSo) {
                Swal.fire({
                    title: "SO Belum Dipilih!",
                    text: "Pilih SO aktif dulu bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }
            let baseRows = window.cachedAppksoDetail || [];
            if (baseRows.length === 0) {
                Swal.fire({
                    title: "Data Kosong!",
                    text: "Tidak ada data APPKSO untuk di-export bro!",
                    confirmButtonColor: "#fe6807",
                });
                return;
            }
            $("#modal-tgl-so-export, #modal-tgl-posisi-export").val("");

            // Hitung jumlah sheet (operator unik)
            let oprSet = new Set(
                baseRows.map((r) => String(r.opr || "UNKNOWN").trim()),
            );
            let totalSheet = oprSet.size;
            let totalRows = baseRows.length;

            // Inject info ke modal (tambahkan elemen jika belum ada)
            let infoEl = document.getElementById("modal-export-info");
            if (infoEl) {
                infoEl.innerHTML = `
        <div class="d-flex justify-content-between">
            <span class="text-muted">SO Aktif</span>
            <span class="fw-bold text-orange">${targetSo}</span>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <span class="text-muted">Jumlah Sheet (Operator)</span>
            <span class="fw-bold text-dark">${totalSheet} sheet</span>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <span class="text-muted">Total Baris Data</span>
            <span class="fw-bold text-dark">${totalRows.toLocaleString()} baris</span>
        </div>
    `;
            }

            // Buka modal
            new bootstrap.Modal(
                document.getElementById("modal-export-excel-appkso"),
            ).show();
        });

    // ==========================================
    // PROSES DOWNLOAD DARI MODAL
    // ==========================================
    $(document)
        .off("click", "#btn-do-export-excel")
        .on("click", "#btn-do-export-excel", async function () {
            let targetSo = $("#so_select").val();
            let baseRows = window.cachedAppksoDetail || [];
            let displayTglSo = $("#modal-tgl-so-export").val()
                ? formatTglExport($("#modal-tgl-so-export").val())
                : "-";
            let displayTglPos = $("#modal-tgl-posisi-export").val()
                ? formatTglExport($("#modal-tgl-posisi-export").val())
                : "-";

            // Tutup modal
            let modalEl = bootstrap.Modal.getInstance(
                document.getElementById("modal-export-excel-appkso"),
            );
            if (modalEl) modalEl.hide();

            let operatorMap = {};
            baseRows.forEach((row) => {
                let code = String(row.opr || "UNKNOWN").trim();
                if (!operatorMap[code]) {
                    operatorMap[code] = {
                        oprname: row.oprname ?? "Tanpa Nama",
                        rows: [],
                    };
                }
                operatorMap[code].rows.push(row);
            });

            let sortedOprKeys = Object.keys(operatorMap).sort((a, b) => {
                return operatorMap[a].oprname
                    .toLowerCase()
                    .localeCompare(
                        operatorMap[b].oprname.toLowerCase(),
                        undefined,
                        { sensitivity: "base" },
                    );
            });

            const wb = new ExcelJS.Workbook();
            wb.creator = "APPKSO System";
            wb.created = new Date();

            const COLOR_HEADER_BG = "FF1a1a1a";
            const COLOR_HEADER_FG = "FFFFFFFF";
            const COLOR_TOTAL_BG = "FFf2f2f2";

            sortedOprKeys.forEach((oprCode) => {
                let opr = operatorMap[oprCode];
                let oprName = opr.oprname;
                let rows = opr.rows;

                let uniqueAuditors = [
                    ...new Set(
                        rows
                            .filter(
                                (r) => r.auditor_nama && r.auditor_nama !== "-",
                            )
                            .map((r) => r.auditor_nama.trim()),
                    ),
                ];
                let auditorNamaExcel =
                    uniqueAuditors.length > 0
                        ? uniqueAuditors.join(", ").toUpperCase()
                        : "-";

                rows.sort((a, b) => {
                    let da = a.nokso ? String(a.nokso) : "";
                    let db = b.nokso ? String(b.nokso) : "";
                    return da.localeCompare(db, undefined, {
                        numeric: true,
                        sensitivity: "base",
                    });
                });

                let sheetName = sanitizeSheetName(oprName);
                let baseName = sheetName;
                let counter = 2;
                while (wb.worksheets.find((s) => s.name === sheetName)) {
                    sheetName = sanitizeSheetName(
                        baseName.substring(0, 28) + "_" + counter++,
                    );
                }

                const ws = wb.addWorksheet(sheetName);
                ws.columns = [
                    { key: "no", width: 5 },
                    { key: "doc", width: 18 },
                    { key: "item", width: 18 },
                    { key: "desc", width: 42 },
                    { key: "qty", width: 12 },
                    { key: "ket", width: 22 },
                ];

                ws.mergeCells("A1:D1");
                let cellJudul = ws.getCell("A1");
                cellJudul.value = "REKAP KARTU STOCK OPNAME";
                cellJudul.font = { bold: true, size: 16, name: "Arial" };
                cellJudul.alignment = {
                    vertical: "middle",
                    horizontal: "left",
                };
                ws.getRow(1).height = 28;

                ws.getRow(2).height = 20;
                ws.getCell("A2").value = "TGL STOCK OPNAME";
                ws.getCell("A2").font = { name: "Arial", size: 10 };
                ws.getCell("C2").value = ": " + displayTglSo;
                ws.getCell("C2").font = { bold: true, name: "Arial", size: 10 };

                ws.mergeCells("D2:D3");
                let cellPic = ws.getCell("D2");
                cellPic.value = oprName.toUpperCase();
                cellPic.font = { bold: true, name: "Arial", size: 11 };
                cellPic.alignment = {
                    vertical: "middle",
                    horizontal: "center",
                    wrapText: true,
                };
                cellPic.border = thinBorder();

                ws.mergeCells("E2:F3");
                let cellAuditor = ws.getCell("E2");
                cellAuditor.value = auditorNamaExcel;
                cellAuditor.font = { bold: true, name: "Arial", size: 11 };
                cellAuditor.alignment = {
                    vertical: "middle",
                    horizontal: "center",
                    wrapText: true,
                };
                cellAuditor.border = thinBorder();

                ws.getRow(3).height = 20;
                ws.getCell("A3").value = "TGL POSISI STOCK";
                ws.getCell("A3").font = { name: "Arial", size: 10 };
                ws.getCell("C3").value = ": " + displayTglPos;
                ws.getCell("C3").font = { bold: true, name: "Arial", size: 10 };

                ws.getRow(4).height = 20;
                ws.getCell("A4").value = "JUMLAH KARTU STOCK";
                ws.getCell("A4").font = { name: "Arial", size: 10 };
                ws.getCell("C4").value = ": " + rows.length + " Lembar";
                ws.getCell("C4").font = { bold: true, name: "Arial", size: 10 };

                ws.getCell("D4").value = "Team Gud. Ban";
                ws.getCell("D4").font = { name: "Arial", size: 10 };
                ws.getCell("D4").alignment = {
                    vertical: "middle",
                    horizontal: "center",
                };
                ws.getCell("D4").border = thinBorder();

                ws.mergeCells("E4:F4");
                ws.getCell("E4").value = "Team SO / Audit";
                ws.getCell("E4").font = { name: "Arial", size: 10 };
                ws.getCell("E4").alignment = {
                    vertical: "middle",
                    horizontal: "center",
                };
                ws.getCell("E4").border = thinBorder();

                ws.getRow(5).height = 8;
                ws.getRow(6).height = 22;

                [
                    { col: "A", label: "NO", align: "center" },
                    { col: "B", label: "NO. DOCUMENT", align: "center" },
                    { col: "C", label: "ITEM CODE", align: "center" },
                    { col: "D", label: "DESCRIPTION", align: "left" },
                    { col: "E", label: "QTY", align: "right" },
                    { col: "F", label: "KET", align: "center" },
                ].forEach((h) => {
                    let cell = ws.getCell(h.col + "6");
                    cell.value = h.label;
                    cell.font = {
                        bold: true,
                        color: { argb: COLOR_HEADER_FG },
                        name: "Arial",
                        size: 10,
                    };
                    cell.fill = {
                        type: "pattern",
                        pattern: "solid",
                        fgColor: { argb: COLOR_HEADER_BG },
                    };
                    cell.alignment = {
                        vertical: "middle",
                        horizontal: h.align,
                    };
                    cell.border = mediumBorder();
                });

                let totalQty = 0;
                let dataStart = 7;

                rows.forEach((row, idx) => {
                    let qty = row.qty ? parseInt(row.qty) : 0;
                    totalQty += qty;
                    let rowNum = dataStart + idx;
                    let bgColor = idx % 2 === 0 ? "FFFFFFFF" : "FFF9F9F9";

                    let setCell = (col, val, alignH) => {
                        let cell = ws.getCell(col + rowNum);
                        cell.value = val;
                        cell.font = { name: "Arial", size: 10 };
                        cell.alignment = {
                            vertical: "middle",
                            horizontal: alignH,
                        };
                        cell.border = thinBorder();
                        cell.fill = {
                            type: "pattern",
                            pattern: "solid",
                            fgColor: { argb: bgColor },
                        };
                    };

                    setCell("A", idx + 1, "center");
                    setCell("B", row.nokso ?? "-", "center");
                    setCell("C", row.item ?? "-", "center");
                    setCell("D", row.deskripsi ?? "-", "left");

                    let cellQty = ws.getCell("E" + rowNum);
                    cellQty.value = qty;
                    cellQty.numFmt = "#,##0";
                    cellQty.font = { bold: true, name: "Arial", size: 10 };
                    cellQty.alignment = {
                        vertical: "middle",
                        horizontal: "right",
                    };
                    cellQty.border = thinBorder();
                    cellQty.fill = {
                        type: "pattern",
                        pattern: "solid",
                        fgColor: { argb: bgColor },
                    };

                    setCell("F", "", "center");
                    ws.getRow(rowNum).height = 18;
                });

                let totalRowNum = dataStart + rows.length;
                ws.getRow(totalRowNum).height = 22;

                ws.mergeCells(`A${totalRowNum}:D${totalRowNum}`);
                let cellTotalLabel = ws.getCell(`A${totalRowNum}`);
                cellTotalLabel.value = "TOTAL";
                cellTotalLabel.font = { bold: true, size: 12, name: "Arial" };
                cellTotalLabel.alignment = {
                    vertical: "middle",
                    horizontal: "center",
                };
                cellTotalLabel.border = mediumBorder();
                cellTotalLabel.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: COLOR_TOTAL_BG },
                };

                let cellTotalQty = ws.getCell(`E${totalRowNum}`);
                cellTotalQty.value = totalQty;
                cellTotalQty.numFmt = "#,##0";
                cellTotalQty.font = { bold: true, size: 13, name: "Arial" };
                cellTotalQty.alignment = {
                    vertical: "middle",
                    horizontal: "right",
                };
                cellTotalQty.border = mediumBorder();
                cellTotalQty.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: COLOR_TOTAL_BG },
                };

                let cellTotalKet = ws.getCell(`F${totalRowNum}`);
                cellTotalKet.border = mediumBorder();
                cellTotalKet.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: COLOR_TOTAL_BG },
                };

                ws.views = [{ state: "frozen", ySplit: 6 }];
            });

            let timestamp = new Date()
                .toISOString()
                .slice(0, 10)
                .replace(/-/g, "");
            let fileName = `APPKSO_${targetSo}_${timestamp}.xlsx`;

            try {
                const buffer = await wb.xlsx.writeBuffer();
                const blob = new Blob([buffer], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
                const url = URL.createObjectURL(blob);
                const link = document.createElement("a");
                link.href = url;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                setTimeout(() => {
                    Swal.fire({
                        title: "Export Sukses! 🎉",
                        html: `File <strong>${fileName}</strong> berhasil di-download bro!<br>Total: <strong>${sortedOprKeys.length} sheet</strong> operator.`,
                        confirmButtonColor: "#fe6807",
                        timer: 5000,
                    });
                }, 300);
            } catch (err) {
                Swal.fire({
                    title: "Export Gagal!",
                    text: "Error ExcelJS: " + err.message,
                    confirmButtonColor: "#d33",
                });
            }
        });
});

// =============================================
// DOWNLOAD UPLOAD ORACLE XLSM
// =============================================
function downloadUploadOracleXlsm() {
    const opr = document.getElementById("selectOprUpload").value;
    const soName = document.getElementById("so_select")?.value || "";

    if (!opr) return;

    const btn = document.getElementById("btnDownloadXlsm");
    btn.disabled = true;
    btn.innerHTML = `<i data-lucide="loader" style="width:12px;height:12px;"></i> Generating...`;
    if (window.lucide) lucide.createIcons({ nodes: [btn] });

    fetch(
        `/appkso/generate-xlsm?opr=${encodeURIComponent(opr)}&so_name=${encodeURIComponent(soName)}`,
    )
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                const path =
                    "\\\\10.129.48.179\\00. DATA BARCODE DESKTOP\\00. UPLOAD TAG COUNTS ORACLE\\" +
                    data.filename;

                const overlay = document.createElement("div");
                overlay.id = "customModalOverlay";
                overlay.style.cssText = `
                    position:fixed; top:0; left:0; width:100%; height:100%;
                    background:rgba(0,0,0,0.5); z-index:99999;
                    display:flex; align-items:center; justify-content:center;
                `;

                overlay.innerHTML = `
                    <div style="background:#fff; border-radius:8px; padding:24px; width:480px; max-width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                        <div style="text-align:center; margin-bottom:16px;">
                            <div style="font-size:48px;">✅</div>
                            <h5 style="margin:8px 0 4px; font-size:18px; font-weight:700;">File Siap!</h5>
                            <p style="font-size:13px; color:#6c757d; margin:0;">
                                <b>Operator:</b> ${data.opr} &nbsp;|&nbsp;
                                <b>SKU:</b> ${data.total_sku} &nbsp;|&nbsp;
                                <b>QTY:</b> ${data.total_qty}
                            </p>
                        </div>
                        <hr style="margin:12px 0;">
                        <p style="font-size:12px; margin-bottom:6px; font-weight:600;">📁 Lokasi File:</p>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <input
                                id="pathInput"
                                type="text"
                                value="${path}"
                                readonly
                                style="flex:1; font-size:11px; padding:6px 8px; border:1px solid #dee2e6; border-radius:4px; background:#f8f9fa;"
                            />
                            <button id="btnCopyPath" style="
                                padding:6px 12px; font-size:12px; white-space:nowrap;
                                background:#0d6efd; color:#fff; border:none;
                                border-radius:4px; cursor:pointer;
                            ">📋 Salin</button>
                        </div>
                        <p style="font-size:11px; color:#6c757d; margin-top:8px;">
                            Buka File Explorer → paste path di address bar → double-click file untuk upload ke Oracle.
                        </p>
                        <div style="text-align:right; margin-top:16px;">
                            <button id="btnTutupModal" style="
                                padding:6px 20px; font-size:13px;
                                background:#6c757d; color:#fff; border:none;
                                border-radius:4px; cursor:pointer;
                            ">Tutup</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                document
                    .getElementById("btnCopyPath")
                    .addEventListener("click", function () {
                        const input = document.getElementById("pathInput");
                        input.select();
                        document.execCommand("copy");
                        this.textContent = "✅ Tersalin!";
                        this.style.background = "#198754";
                    });

                document
                    .getElementById("btnTutupModal")
                    .addEventListener("click", function () {
                        document.getElementById("customModalOverlay").remove();
                    });

                overlay.addEventListener("click", function (e) {
                    if (e.target === overlay) overlay.remove();
                });
            } else {
                alert("Gagal: " + data.message);
            }
        })
        .catch(() => {
            alert("Error: Gagal generate file.");
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="download" style="width:12px;height:12px;"></i> Generate File`;
            if (window.lucide) lucide.createIcons({ nodes: [btn] });
        });
}

function loadAppksoViewFilters() {
    let viewWhSelect = $("#appkso-view-warehouse");
    if (!viewWhSelect || viewWhSelect.length === 0) return;

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

            // 🔥 Auto-select BPW dan langsung load data
            viewWhSelect.val("BPW").trigger("change");
        }
    });
}

// ==========================================
// 🎯 EXPORT BERITA ACARA (BA) STOCK OPNAME
// ==========================================
async function doExportBA(targetSo, tglSo, tglCutoff) {
    Swal.fire({
        title: "Menarik Data Server...",
        text: "Mempersiapkan data Onhand & Counted dari Database",
        allowOutsideClick: false,
        onOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        let response = await fetch(
            `/appkso/export-ba-data?so_name=${encodeURIComponent(targetSo)}`,
        );
        let resData = await response.json();

        if (!resData.success) {
            Swal.fire({
                title: "Error Server!",
                text: resData.message,
                type: "error",
            });
            return;
        }
        if (resData.data.length === 0) {
            Swal.fire({
                title: "Data Kosong!",
                text: "Tidak ada data scan untuk SO ini!",
                type: "warning",
            });
            return;
        }
        let aggregatedData = resData.data;

        const formatTglIndo = (dateStr) => {
            if (!dateStr) return "-";
            const dateObj = new Date(dateStr);
            return dateObj.toLocaleDateString("id-ID", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric",
            });
        };

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet("Lamp. BA SO");

        ws.columns = [
            { key: "A", width: 5 }, // No
            { key: "B", width: 18 }, // Kode Item
            { key: "C", width: 45 }, // Deskripsi
            { key: "D", width: 8 }, // UOM
            { key: "E", width: 12 }, // Onhand (A)
            { key: "F", width: 12 }, // Counted (B)
            { key: "G", width: 15 }, // Selisih Admin (C)
            { key: "H", width: 15 }, // Selisih Fisik (D)
            { key: "I", width: 45 }, // Penjelasan Selisih
        ];

        const borderThin = {
            top: { style: "thin" },
            left: { style: "thin" },
            bottom: { style: "thin" },
            right: { style: "thin" },
        };
        const fillYellow = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FFFFE699" },
        };

        // 4. HEADER SURAT
        ws.mergeCells("A1:I1");
        ws.getCell("A1").value = "LAMPIRAN BERITA ACARA STOCK OPNAME";
        ws.getCell("A1").font = { bold: true, name: "Arial", size: 11 };

        ws.mergeCells("A2:C2");
        ws.getCell("A2").value = formatTglIndo(tglSo);
        ws.getCell("A2").font = { bold: true, name: "Arial", size: 11 };

        ws.mergeCells("A3:C3");
        ws.getCell("A3").value = `Cut off Stock : ${formatTglIndo(tglCutoff)}`;
        ws.getCell("A3").font = { bold: true, name: "Arial", size: 11 };

        // 5. HEADER TABEL UTAMA
        ws.getCell("E5").value = "(A)";
        ws.getCell("F5").value = "(B)";
        ws.getCell("G5").value = "(C)";
        ws.getCell("H5").value = "(D) = B-A-C";
        ["E5", "F5", "G5", "H5"].forEach((cell) => {
            ws.getCell(cell).font = { italic: true, name: "Arial", size: 10 };
            ws.getCell(cell).alignment = { horizontal: "center" };
        });

        const headers = [
            "No",
            "Kode Item",
            "Deskripsi",
            "UOM",
            "Onhand",
            "Counted",
            "Selisih Admin*",
            "Selisih Fisik",
            "Penjelasan Selisih",
        ];
        let headerRow = ws.getRow(6);
        headers.forEach((text, i) => {
            let cell = headerRow.getCell(i + 1);
            cell.value = text;
            cell.font = { bold: true, name: "Arial", size: 10 };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.border = borderThin;
        });

        // 6. ISI TABEL DATA
        let startRow = 7;
        aggregatedData.forEach((data, index) => {
            let row = ws.getRow(startRow + index);

            row.getCell(1).value = index + 1;
            row.getCell(2).value = data.item;
            row.getCell(3).value = data.desc;
            row.getCell(4).value = "PCS";
            row.getCell(5).value = data.onhand;
            row.getCell(6).value = data.counted;
            row.getCell(7).value = 0; // REQ: Selisih admin dibuat 0

            row.getCell(8).value = {
                formula: `F${startRow + index}-E${startRow + index}-G${startRow + index}`,
            };
            row.getCell(9).value = data.similar;

            // REQ: Rata Kanan untuk Qty (E, F, G, H)
            [1, 4].forEach(
                (col) =>
                    (row.getCell(col).alignment = {
                        horizontal: "center",
                        vertical: "middle",
                    }),
            );
            [2, 3, 9].forEach(
                (col) =>
                    (row.getCell(col).alignment = {
                        horizontal: "left",
                        vertical: "middle",
                    }),
            );
            [5, 6, 7, 8].forEach((col) => {
                row.getCell(col).alignment = {
                    horizontal: "right",
                    vertical: "middle",
                };
                row.getCell(col).numFmt = "#,##0";
            });

            for (let i = 1; i <= 9; i++) {
                let c = row.getCell(i);
                c.border = borderThin;
                c.font = { name: "Arial", size: 10 };
            }
        });

        // 7. FOOTER TABEL (TOTAL)
        let totalRowIdx = startRow + aggregatedData.length;
        ws.mergeCells(`A${totalRowIdx}:C${totalRowIdx}`);
        let cellTotalLabel = ws.getCell(`A${totalRowIdx}`);
        cellTotalLabel.value = "Total";
        cellTotalLabel.alignment = { horizontal: "center", vertical: "middle" };
        cellTotalLabel.font = { bold: true, name: "Arial", size: 10 };

        ws.getCell(`D${totalRowIdx}`).value = "PCS";
        ws.getCell(`D${totalRowIdx}`).alignment = {
            horizontal: "center",
            vertical: "middle",
        };

        // REQ: Rata Kanan untuk Total
        ["E", "F", "G", "H"].forEach((col) => {
            let cell = ws.getCell(`${col}${totalRowIdx}`);
            cell.value = {
                formula: `SUM(${col}${startRow}:${col}${totalRowIdx - 1})`,
            };
            cell.numFmt = "#,##0";
            cell.alignment = { horizontal: "right", vertical: "middle" };
        });

        for (let i = 1; i <= 9; i++) {
            ws.getCell(totalRowIdx, i).border = borderThin;
            ws.getCell(totalRowIdx, i).font = {
                bold: true,
                name: "Arial",
                size: 10,
            };
        }

        // 8. SECTION ACTION PLAN
        let apStart = totalRowIdx + 2;
        ws.getCell(`B${apStart}`).value = "ACTION PLAN";
        ws.getCell(`B${apStart}`).font = {
            bold: true,
            name: "Arial",
            size: 10,
        };

        let apHeadRow = ws.getRow(apStart + 1);
        apHeadRow.getCell(1).value = "No.";
        apHeadRow.getCell(2).value = "Faktor";
        ws.mergeCells(`C${apStart + 1}:E${apStart + 1}`);
        ws.getCell(`C${apStart + 1}`).value = "Root Cause";
        ws.mergeCells(`F${apStart + 1}:I${apStart + 1}`);
        ws.getCell(`F${apStart + 1}`).value = "Corrective Action";

        [1, 2, 3, 6].forEach((col) => {
            let c = apHeadRow.getCell(col);
            c.font = { bold: true, name: "Arial", size: 10 };
            c.alignment = { horizontal: "center" };
            c.border = borderThin;
        });

        for (let i = 1; i <= 2; i++) {
            let rIdx = apStart + 1 + i;
            ws.getCell(`A${rIdx}`).value = i;
            ws.getCell(`A${rIdx}`).alignment = { horizontal: "center" };
            ws.mergeCells(`C${rIdx}:E${rIdx}`);
            ws.mergeCells(`F${rIdx}:I${rIdx}`);
            [1, 2, 3, 6].forEach(
                (col) => (ws.getCell(rIdx, col).border = borderThin),
            );
        }

        // 9. TANDA TANGAN
        let ttdStart = apStart + 5;
        let todayBerjalan = new Date().toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
        ws.getCell(`B${ttdStart}`).value = `Tangerang, ${todayBerjalan}`;
        ws.getCell(`B${ttdStart}`).font = {
            bold: true,
            name: "Arial",
            size: 10,
        };

        ws.getCell(`B${ttdStart + 2}`).value = "Dibuat oleh,";
        ws.getCell(`E${ttdStart + 2}`).value = "Diketahui oleh,";
        ws.getCell(`G${ttdStart + 2}`).value = "Disetujui oleh,";

        [2, 5, 7].forEach((c) => {
            ws.getCell(ttdStart + 2, c).font = {
                bold: true,
                name: "Arial",
                size: 10,
            };
            ws.getCell(ttdStart + 2, c).alignment = { horizontal: "center" };
        });

        let ttdName = ttdStart + 7;
        ws.getCell(`B${ttdName}`).value = "Ricky Juliana";

        ws.getCell(`E${ttdName}`).value = "Nurul Hidayanto";

        ws.getCell(`G${ttdName}`).value = "Edward Supandi";
        ws.getCell(`H${ttdName}`).value = "Ronald Sitompul";
        ws.getCell(`I${ttdName}`).value = "Dax Ramadani";

        ws.getCell(`B${ttdName + 1}`).value = "(Sub Dept. Head Gudang)";
        ws.getCell(`E${ttdName + 1}`).value = "(Dept. Head Gudang)";
        ws.getCell(`G${ttdName + 1}`).value = "(Deputy HOD Logistic)";
        ws.getCell(`H${ttdName + 1}`).value = "(HOD Logistic & GRM)";
        ws.getCell(`I${ttdName + 1}`).value = "(HOD SCM, Logistic & Shipping)";

        [2, 5, 7, 8, 9].forEach((c) => {
            ws.getCell(ttdName, c).font = {
                bold: true,
                name: "Arial",
                size: 10,
            };
            ws.getCell(ttdName, c).alignment = { horizontal: "center" };
            ws.getCell(ttdName + 1, c).font = {
                bold: true,
                name: "Arial",
                size: 10,
            };
            ws.getCell(ttdName + 1, c).alignment = { horizontal: "center" };
        });

        // 10. NOTES
        let noteStart = ttdName + 3;
        ws.getCell(`B${noteStart}`).value = "Note :";
        ws.getCell(`B${noteStart + 1}`).value =
            "* Selisih Admin merupakan qty yang belum dientri pada sistem, dengan penjelasan sebagai berikut:";
        ws.getCell(`B${noteStart + 2}`).value =
            "Qty(+) merupakan transaksi barang masuk yang belum diinput di sistem";
        ws.getCell(`B${noteStart + 3}`).value =
            "Qty(-) merupakan transaksi barang keluar yang belum diinput di sistem";

        [0, 1, 2, 3].forEach((offset) => {
            let cell = ws.getCell(`B${noteStart + offset}`);
            cell.font = { italic: offset > 0, name: "Arial", size: 10 };
        });

        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        let timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, "");
        link.href = url;
        link.download = `BA_StockOpname_${targetSo}_${timestamp}.xlsx`;

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        Swal.fire({
            title: "Export BA Sukses! 🎉",
            text: "Berhasil ditarik dari Database & di-Export bro!",
            type: "success",
            confirmButtonColor: "#198754",
            timer: 3000,
        });
    } catch (err) {
        Swal.fire({
            title: "Export Gagal!",
            text: "Ada error: " + err.message,
            type: "error",
            confirmButtonColor: "#d33",
        });
    }
}

// 11. LISTENER AUTORUN
document.addEventListener("DOMContentLoaded", function () {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("auto_export_ba") == "1") {
            let targetSo = urlParams.get("so_name");
            let tglSo = urlParams.get("tgl_so");
            let tglCutoff = urlParams.get("tgl_cutoff");

            const newUrl =
                window.location.protocol +
                "//" +
                window.location.host +
                window.location.pathname +
                (targetSo ? "?so_name=" + encodeURIComponent(targetSo) : "");
            window.history.replaceState({ path: newUrl }, "", newUrl);

            setTimeout(() => {
                if (targetSo) doExportBA(targetSo, tglSo, tglCutoff);
            }, 800);
        }
    } catch (e) {
        console.error("Gagal menjalankan auto export BA:", e);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    // 1. KLIK BARIS RESUME PIC UNTUK LIHAT MODAL
    $(document).on("click", ".clickable-row", function () {
        const oprId = $(this).data("opr");
        const oprName = $(this).data("oprname") || "-";

        $("#modalTitleScanHistory").text(
            `RIWAYAT SCAN OPERATOR : ${oprName.toUpperCase()} (${oprId})`,
        );

        const details = window.cachedAppksoDetail || [];
        const filteredData = details.filter(
            (item) => String(item.opr) === String(oprId),
        );

        const tbody = $("#tbodyScanHistory");
        tbody.empty();

        if (filteredData.length === 0) {
            tbody.append(
                `<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada detail riwayat scan.</td></tr>`,
            );
        } else {
            filteredData.forEach((row, index) => {
                const qtyFormatted = Number(row.qty).toLocaleString("en-US");
                tbody.append(`
                    <tr>
                        <td class="text-center text-muted">${index + 1}</td>
                        <td class="text-center fw-bold text-secondary">${row.opr || "-"}</td>
                        <td class="fw-semibold text-dark">${row.oprname || "-"}</td>
                        <td class="text-center text-orange fw-bold">${row.nokso || "-"}</td>
                        <td class="text-center fw-bold">${row.item || "-"}</td>
                        <td class="text-start text-truncate" style="max-width: 250px;" title="${row.deskripsi || "-"}">
                            ${row.deskripsi || "-"}
                        </td>
                        <td class="text-end pe-3 fw-black text-dark">${qtyFormatted} PCS</td>
                    </tr>
                `);
            });
        }

        const scanModal = new bootstrap.Modal(
            document.getElementById("modalScanHistory"),
        );
        scanModal.show();
    });

    // 2. FIX WARNING ARIA-HIDDEN SAAT MODAL DITUTUP
    $("#modalScanHistory").on("hide.bs.modal", function () {
        if (document.activeElement) {
            document.activeElement.blur();
        }
    });
});
