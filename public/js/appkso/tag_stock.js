/**
 * ⚡ TAG STOCK CLEAN VERSION FIXED + VALIDASI + CEK DOC + MODAL SCAN ⚡
 */

window.initTagStockMenu = function () {
    loadTagStockInitialFilters();
};

function renderNormalHeader() {
    $("#thead-tagstock").html(`
        <tr>
            <th width="5%" class="text-center bg-light border-dark py-2">No.</th>
            <th width="12%" class="bg-light border-dark">Lot</th>
            <th width="12%" class="bg-light border-dark">No. Doc</th>
            <th width="12%" class="bg-light border-dark">Item</th>
            <th class="bg-light border-dark text-start">Deskripsi Master Size</th>
            <th width="10%" class="text-center bg-light border-dark">Jumlah Rak</th>
            <th width="10%" class="text-end bg-light border-dark">Qty</th>
            <th width="10%" class="text-end bg-light border-dark">Jumlah Aktual</th>
        </tr>
    `);
}

function renderCekDocHeader() {
    $("#thead-tagstock").html(`
        <tr>
            <th width="5%" class="text-center bg-light border-dark py-2">No.</th>
            <th width="12%" class="bg-light border-dark">PIC</th>
            <th width="10%" class="bg-light border-dark">Lot</th>
            <th width="12%" class="bg-light border-dark">No. Doc</th>
            <th width="12%" class="bg-light border-dark">Item</th>
            <th class="bg-light border-dark text-start">Deskripsi Master Size</th>
            <th width="10%" class="text-end bg-light border-dark">Qty Tag Stock</th>
            <th width="10%" class="text-end bg-light border-dark">Qty APPKSO</th>
            <th width="10%" class="text-end bg-light border-dark">Selisih</th>
        </tr>
    `);
}

function renderValidasiHeader() {
    $("#thead-tagstock").html(`
        <tr>
            <th width="5%" class="text-center bg-light border-dark py-2">No.</th>
            <th width="12%" class="bg-light border-dark">Lot</th>
            <th width="12%" class="bg-light border-dark">No. Doc</th>
            <th width="12%" class="bg-light border-dark">Item</th>
            <th class="bg-light border-dark text-start">Deskripsi Master Size</th>
            <th width="10%" class="text-center bg-light border-dark">Jumlah Rak</th>
            <th width="10%" class="text-end bg-light border-dark">Qty</th>
            <th width="10%" class="text-end bg-light border-dark">Jumlah Aktual</th>
            <th width="10%" class="text-center bg-light border-dark">Keterangan</th>
        </tr>
    `);
}

function loadTagStockInitialFilters() {
    $.get("/appkso/tag-stock/init-filters", function (res) {
        let whSelect = $("#tag-filter-wh");
        if (!res || res.status !== "success") return;
        whSelect.html('<option value="">⚠️ PILIH GUDANG</option>');
        res.warehouses.forEach((data) => {
            whSelect.append(
                `<option value="${data.warehouse}">
                    ${data.warehouse} - Terakhir Upload : ${data.last_upload}
                </option>`,
            );
        });
        if (whSelect.find('option[value="BPW"]').length) {
            whSelect.val("BPW").trigger("change");
        }
    });
}

$(document)
    .off("change", "#tag-filter-wh")
    .on("change", "#tag-filter-wh", function () {
        isValidasiMode = false;
        let wh = $(this).val();
        let opSelect = $("#tag-filter-operator");
        opSelect.val("").prop("disabled", true);
        $("#tag-filter-doc-start").html(
            '<option value="">-- DOC AWAL --</option>',
        );
        $("#tag-filter-doc-end").html(
            '<option value="">-- DOC AKHIR --</option>',
        );
        $("#tbody-tagstock-rows").html(
            `<tr><td colspan="8" class="text-center">Pilih operator...</td></tr>`,
        );
        $("#tfoot-tagstock-summary").addClass("d-none");
        $("#btn-validasi-tag").addClass("d-none");
        if (wh) {
            $("#btn-cek-doc").removeClass("d-none");
            renderNormalHeader();
        } else {
            $("#btn-cek-doc").addClass("d-none");
        }
        if (!wh) return;

        $.get("/appkso/tag-stock/operators?warehouse=" + wh, function (res) {
            if (!res || res.status !== "success") return;
            opSelect.prop("disabled", false);
            opSelect.html('<option value="">-- PILIH OPERATOR --</option>');
            res.operators.forEach((op) => {
                opSelect.append(`
                <option value="${op.no_penneng}"
                    data-nama="${op.nama}"
                    data-gedung="${op.gedung}"
                    data-lot="${op.combined_lot}">
                    ${op.nama} (${op.no_penneng})
                </option>
            `);
            });
        });
    });

$(document)
    .off("change", "#tag-filter-operator")
    .on("change", "#tag-filter-operator", function () {
        let opId = $(this).val();
        if (opId) {
            $("#doc-filter-container").removeClass("d-none");
            $("#btn-cek-doc").addClass("d-none");
            $("#btn-validasi-tag").removeClass("d-none");
            renderNormalHeader();
            loadTagStockData();
            loadDocFilter();
        } else {
            $("#doc-filter-container").addClass("d-none");
            $("#btn-validasi-tag").addClass("d-none");
            $("#btn-cek-doc").removeClass("d-none");
            $("#tbody-tagstock-rows").html(
                `<tr><td colspan="8" class="text-center">Pilih operator...</td></tr>`,
            );
        }
    });

function loadDocFilter() {
    $.post(
        "/appkso/tag-stock/process-rows",
        {
            warehouse: $("#tag-filter-wh").val(),
            operator_id: $("#tag-filter-operator").val(),
        },
        function (res) {
            if (!res || res.status !== "success") return;
            let docs = [...new Set(res.master_data.map((x) => x.no_doc))];
            let start = $("#tag-filter-doc-start");
            let end = $("#tag-filter-doc-end");
            start.html('<option value="">-- DOC AWAL --</option>');
            end.html('<option value="">-- DOC AKHIR --</option>');
            docs.forEach((doc) => {
                start.append(`<option value="${doc}">${doc}</option>`);
                end.append(`<option value="${doc}">${doc}</option>`);
            });
        },
    );
}

$(document)
    .off("change", "#tag-filter-doc-start, #tag-filter-doc-end")
    .on("change", "#tag-filter-doc-start, #tag-filter-doc-end", function () {
        loadTagStockData();
    });

window.openScanHistory = function (doc, item) {
    let wh = $("#tag-filter-wh").val();
    if (!wh || !doc || !item) return;

    $("#tbodyScanHistory").html(
        `<tr><td colspan="7" class="text-center py-4 text-primary">⏳ Memuat riwayat scan...</td></tr>`,
    );
    let scanModal = new bootstrap.Modal(
        document.getElementById("modalScanHistory"),
    );
    scanModal.show();

    $.post(
        "/appkso/tag-stock/scan-history",
        { warehouse: wh, doc: doc, item: item },
        function (res) {
            if (!res || res.status !== "success") {
                $("#tbodyScanHistory").html(
                    `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data histori</td></tr>`,
                );
                return;
            }
            let html = "";
            if (res.data.length === 0) {
                html = `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada history scan untuk item ini.</td></tr>`;
            } else {
                res.data.forEach((r, idx) => {
                    html += `
                <tr>
                    <td class="text-center">${idx + 1}</td>
                    <td>${r.opr || "-"}</td>
                    <td>${r.oprname || "-"}</td>
                    <td>${r.nokso}</td>
                    <td>${r.item}</td>
                    <td style="white-space: normal;">${r.deskripsi || "-"}</td>
                    <td class="text-end text-primary fw-bold" style="font-size:14px;">${parseInt(r.qty).toLocaleString("id-ID")}</td>
                </tr>
            `;
                });
            }
            $("#tbodyScanHistory").html(html);
        },
    );
};

function loadTagStockData() {
    let wh = $("#tag-filter-wh").val();
    let opId = $("#tag-filter-operator").val();
    let docStart = $("#tag-filter-doc-start").val();
    let docEnd = $("#tag-filter-doc-end").val();

    if (docStart && docEnd && docStart > docEnd) {
        Swal.fire({
            type: "error",
            title: "Waduh...",
            text: "Doc Awal nggak boleh lebih besar dari Doc Akhir bro!",
        });
        return;
    }

    if (!wh || !opId) return;

    $("#tbody-tagstock-rows").html(
        `<tr><td colspan="8" class="text-center">Loading...</td></tr>`,
    );
    renderNormalHeader();

    $.post(
        "/appkso/tag-stock/process-rows",
        {
            warehouse: wh,
            operator_id: opId,
            doc_start: docStart,
            doc_end: docEnd,
        },
        function (res) {
            if (!res || res.status !== "success") return;

            let html = "";
            let totalRak = 0;
            let totalQty = 0;

            res.master_data.forEach((row, i) => {
                let rak = parseInt(row.Rak || 0);
                let qty = parseInt(row.Qty || 0);

                totalRak += rak;
                totalQty += qty;

                html += `
                    <tr style="cursor: pointer;" onclick="openScanHistory('${row.no_doc}', '${row.item}')">
                        <td class="text-center">${i + 1}</td>
                        <td>${row.lot_display}</td>
                        <td>${row.no_doc}</td>
                        <td>${row.item}</td>
                        <td>${row.description || "-"}</td>
                        <td class="text-center">${rak}</td>
                        <td class="text-end">${qty.toLocaleString("id-ID")}</td>
                        <td></td>
                    </tr>
                `;
            });

            if (!html) {
                $("#tbody-tagstock-rows").html(
                    `<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>`,
                );
                return;
            }

            $("#tbody-tagstock-rows").html(html);

            $("#tfoot-tagstock-summary").removeClass("d-none").html(`
                <tr>
                    <td colspan="5" class="text-end py-2 text-dark bg-light border-dark">TOTAL RINGKASAN PENUGASAN :</td>
                    <td id="total-summary-rack" class="text-center text-danger font-monospace bg-light border-dark">${totalRak} RAK</td>
                    <td id="total-summary-qty" class="text-end text-primary font-monospace bg-light border-dark">${totalQty.toLocaleString("id-ID")} PCS</td>
                    <td class="bg-light border-dark"></td>
                </tr>
            `);
            $("#btn-print-massal-tag").removeClass("d-none");
        },
    );
}

let isValidasiMode = false;

$(document)
    .off("click", "#btn-validasi-tag")
    .on("click", "#btn-validasi-tag", function () {
        $(this).blur();
        let wh = $("#tag-filter-wh").val();
        let opId = $("#tag-filter-operator").val();
        let docStart = $("#tag-filter-doc-start").val();
        let docEnd = $("#tag-filter-doc-end").val();

        if (!wh || !opId) {
            Swal.fire(
                "Perhatian",
                "Pilih Gudang dan Operator terlebih dahulu!",
                "warning",
            );
            return;
        }

        if (isValidasiMode) {
            isValidasiMode = false;
            $("#btn-validasi-tag")
                .removeClass("btn-success")
                .addClass("btn-warning")
                .html(
                    '<i data-lucide="check-circle" class="me-1" style="width: 14px; height: 14px;"></i> Validasi',
                );
            if (typeof lucide !== "undefined") lucide.createIcons();
            renderNormalHeader();
            loadTagStockData();
            return;
        }

        Swal.fire({
            title: "Mempersiapkan Validasi",
            text: "Mengambil daftar SO Name dari server...",
            allowOutsideClick: false,
            onOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: "/appkso/tag-stock/get-so-names-cntso",
            type: "GET",
            success: function (res) {
                if (!res || res.status !== "success") {
                    Swal.fire("Error", "Gagal memuat daftar SO Name.", "error");
                    return;
                }

                let selectHtml = `<select id="swal-so-name" class="swal2-select" style="width: 80%; padding: 10px; border-radius: 8px;">`;
                selectHtml += `<option value="" disabled selected>-- Pilih SO Name --</option>`;
                res.data.forEach((name) => {
                    selectHtml += `<option value="${name}">${name}</option>`;
                });
                selectHtml += `</select>`;

                Swal.fire({
                    title: "Pilih SO Name (CNTSO)",
                    html: selectHtml,
                    showCancelButton: true,
                    confirmButtonText: "Validasi",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#fe6807",
                    preConfirm: () => {
                        const soName =
                            Swal.getPopup().querySelector(
                                "#swal-so-name",
                            ).value;
                        if (!soName) {
                            Swal.showValidationMessage(
                                `Bro, lo belum milih SO Name!`,
                            );
                        }
                        return { soName: soName };
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeValidasiData(
                            wh,
                            opId,
                            docStart,
                            docEnd,
                            result.value.soName,
                        );
                    }
                });
            },
            error: function (xhr) {
                Swal.fire(
                    "Error",
                    "Gagal terhubung ke server fginvc.",
                    "error",
                );
                console.error(xhr.responseText);
            },
        });
    });

function executeValidasiData(wh, opId, docStart, docEnd, soName) {
    console.log("Mulai validasi untuk:", soName);

    $.ajax({
        url: "/appkso/tag-stock/validasi-appkso",
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            warehouse: wh,
            operator_id: opId,
            doc_start: docStart,
            doc_end: docEnd,
            so_name: soName,
        },
        success: function (res) {
            console.log("DATA DARI SERVER:", res);
            if (res.status === "success") {
                // ⚡ INI YANG KURANG: Kita render datanya ke tabel!
                let html = "";
                let totalRak = 0,
                    totalQty = 0;

                res.master_data.forEach((row, i) => {
                    let rak = parseInt(row.Rak || 0);
                    let qtyTag = parseInt(row.qty_tag || 0);
                    let qtyKso = parseInt(row.qty_appkso || 0);

                    totalRak += rak;
                    totalQty += qtyTag;

                    // Logika Sesuai/Tidak Sesuai
                    let statusBadge =
                        qtyTag === qtyKso
                            ? `<span class="badge bg-success w-100 py-1" style="font-size: 11px;">Sesuai</span>`
                            : `<span class="badge bg-danger w-100 py-1" style="font-size: 11px;">Tidak Sesuai</span>`;

                    html += `
                    <tr>
                        <td class="text-center">${i + 1}</td>
                        <td>${row.lot_display || "-"}</td>
                        <td>${row.no_doc}</td>
                        <td>${row.item}</td>
                        <td>${row.description || "-"}</td>
                        <td class="text-center">${rak}</td>
                        <td class="text-end">${qtyTag.toLocaleString("id-ID")}</td>
                        <td class="text-end fw-bold text-primary">${qtyKso.toLocaleString("id-ID")}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>`;
                });

                $("#tbody-tagstock-rows").html(html);
                alert("Data berhasil ditarik dan tabel terupdate!");
            } else {
                alert("Server error: " + res.message);
            }
        },
        error: function (xhr) {
            console.log("ERROR STATUS:", xhr.status);
            console.log("ERROR RESPON:", xhr.responseText);
            alert(
                "Error: " + xhr.status + " - Cek console buat liat errornya!",
            );
        },
    });
}

$(document).on("click", "#btn-cek-doc", function () {
    alert("Tombol validasi sudah diklik!");
    let wh = $("#tag-filter-wh").val();
    if (!wh) {
        Swal.fire({
            type: "warning",
            title: "Pilih Gudang",
            text: "Pilih gudang dulu bro sebelum Cek Doc!",
        });
        return;
    }

    $("#tbody-tagstock-rows").html(
        `<tr><td colspan="9" class="text-center">Loading Seluruh Data Dokumen...</td></tr>`,
    );
    renderCekDocHeader();
    $("#tfoot-tagstock-summary").addClass("d-none");

    $.post("/appkso/tag-stock/cek-doc", { warehouse: wh }, function (res) {
        if (!res || res.status !== "success") return;

        let html = "";
        res.master_data.forEach((row, i) => {
            let selisih = parseInt(row.selisih || 0);
            let colorClass =
                selisih !== 0 ? "text-danger fw-bold" : "text-success fw-bold";

            html += `
                <tr style="cursor: pointer;" onclick="openScanHistory('${row.no_doc}', '${row.item}')">
                    <td class="text-center">${i + 1}</td>
                    <td class="fw-bold">${row.pic_name || "-"}</td>
                    <td>${row.lot_display}</td>
                    <td>${row.no_doc}</td>
                    <td>${row.item}</td>
                    <td>${row.description || "-"}</td>
                    <td class="text-end">${parseInt(row.qty_tag || 0).toLocaleString("id-ID")}</td>
                    <td class="text-end">${parseInt(row.qty_appkso || 0).toLocaleString("id-ID")}</td>
                    <td class="text-end ${colorClass}">${selisih.toLocaleString("id-ID")}</td>
                </tr>
            `;
        });

        if (!html)
            html = `<tr><td colspan="9" class="text-center text-success fw-bold py-5">Wah mantap! Semua dokumen balance bro, tidak ada selisih.</td></tr>`;
        $("#tbody-tagstock-rows").html(html);
    });
});

function generateTagStockPrint() {
    const table = document.getElementById("table-view-tagstock-list");
    if (!table) {
        alert("Table tidak ditemukan");
        return;
    }
    const opSelected = $("#tag-filter-operator option:selected");
    if (!opSelected.val()) {
        alert("Pilih operator dulu sebelum print");
        return;
    }
    const cloned = table.cloneNode(true);
    const oldTfoot = cloned.querySelector("tfoot");
    if (oldTfoot) oldTfoot.remove();

    cloned.querySelectorAll("tr").forEach((tr) => {
        const txt = tr.innerText.toLowerCase();
        if (txt.includes("ringkasan") || txt.includes("penugasan")) tr.remove();
    });

    let pic = opSelected.text() || "-";
    let gedung = opSelected.attr("data-gedung") || "-";
    let lotRange = opSelected.attr("data-lot") || "-";
    let wh = $("#tag-filter-wh").val() || "-";

    if (pic.includes(" - ")) {
        const parts = pic.split(" - ");
        pic = parts[1] + " (" + parts[0] + ")";
    }

    const grandRow = document.createElement("tr");
    grandRow.style.fontWeight = "bold";
    grandRow.style.background = "#f2f2f2";
    grandRow.className = "grand-total-row";
    grandRow.innerHTML = `
        <td colspan="5" style="border:1px solid #000; text-align:center;">GRAND TOTAL</td>
        <td style="border:1px solid #000; text-align:center;">${$("#total-summary-rack").text() || 0}</td>
        <td style="border:1px solid #000; text-align:center;">${$("#total-summary-qty").text() || 0}</td>
        <td style="border:1px solid #000;"></td>
    `;
    const tbody = cloned.querySelector("tbody");
    if (tbody) tbody.appendChild(grandRow);
    const tableHTML = cloned.outerHTML;
    const win = window.open("", "PRINT", "width=1200,height=800");
    if (!win) {
        alert("Popup blocked browser!");
        return;
    }

    win.document.write(`
<html><head><title>Print Tag Stock</title>
<style>
    @page { size: A4 portrait; margin: 8mm; }
    body { font-family: "Times New Roman", serif; font-size: 12px; }
    .header { text-align: center; margin-bottom: 20px; }
    .header h2 { margin: 0; padding: 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { border: 1px solid #000; padding: 5px; }
    th { background: #eee; text-align: center; }
    td:nth-child(6), td:nth-child(7) { text-align: right; }
    .grand-total-row td { text-align: center !important; font-weight: bold; }
    tr { page-break-inside: avoid; }
</style>
</head><body>
    <div class="header">
        <h2>Monitoring Stock (${wh})</h2>
        <div style="text-align: left; display: inline-block;">
            <b></b>${pic} || <b></b>${gedung} Lot ${lotRange}
        </div>
    </div>
    ${tableHTML}
</body></html>
`);
    win.document.close();
    win.focus();
    setTimeout(() => {
        win.print();
    }, 300);
}

window.openTagStockPrintEngine = function () {
    generateTagStockPrint();
};

$(document)
    .off("click", "#btn-tag-stock")
    .on("click", "#btn-tag-stock", function () {
        let warehouse = $("#tag-filter-wh").val();
        let operatorId = $("#tag-filter-operator").val();
        let docStart = $("#tag-filter-doc-start").val();
        let docEnd = $("#tag-filter-doc-end").val();

        if (!warehouse) {
            Swal.fire({
                type: "warning",
                title: "Perhatian",
                text: "Pilih warehouse dulu bro!",
            });
            return;
        }
        if (docStart && docEnd && docStart > docEnd) {
            Swal.fire({
                type: "error",
                title: "Dokumen Terbalik!",
                text: "Doc Awal nggak boleh lebih besar dari Doc Akhir bro!",
                confirmButtonColor: "#3085d6",
            });
            return;
        }

        let url =
            "/appkso/tag-stock/print" +
            "?warehouse=" +
            encodeURIComponent(warehouse) +
            "&operator_id=" +
            encodeURIComponent(operatorId || "") +
            "&doc_start=" +
            encodeURIComponent(docStart || "") +
            "&doc_end=" +
            encodeURIComponent(docEnd || "");
        window.open(url, "_blank");
    });

window.resetFilters = function () {
    isValidasiMode = false;
    $("#btn-validasi-tag")
        .removeClass("btn-success")
        .addClass("btn-warning")
        .html(
            '<i data-lucide="check-circle" class="me-1" style="width: 14px; height: 14px;"></i> Validasi',
        );
    $("#tag-filter-wh").val("").trigger("change");
    $("#tag-filter-doc-start").html('<option value="">-- DOC AWAL --</option>');
    $("#tag-filter-doc-end").html('<option value="">-- DOC AKHIR --</option>');
    $("#doc-filter-container").addClass("d-none");
    $("#btn-validasi-tag").addClass("d-none");
    $("#btn-cek-doc").addClass("d-none");
    renderNormalHeader();
    $("#tbody-tagstock-rows").html(
        `<tr><td colspan="8" class="text-center text-muted py-5 border-0">Filter sudah di-reset. Silakan pilih kembali gudang dan operator bro.</td></tr>`,
    );
    $("#tfoot-tagstock-summary").addClass("d-none");
    $("#btn-print-massal-tag").addClass("d-none");
    if (typeof lucide !== "undefined") lucide.createIcons();
};

$(document).ready(function () {
    $("body").tooltip({
        selector: '[data-bs-toggle="tooltip"]',
        trigger: "hover",
    });
    $(document).on("click", '[data-bs-toggle="tooltip"]', function () {
        $(this).tooltip("hide");
    });
    window.initTagStockMenu();
});

$(document).ready(function () {
    console.log("jQuery sudah siap!");
});
