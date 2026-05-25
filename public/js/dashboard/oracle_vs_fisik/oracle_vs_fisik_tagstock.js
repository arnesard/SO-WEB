/**
 * ⚡ TAG STOCK CLEAN VERSION FIXED ⚡
 */

window.initTagStockMenu = function () {
    loadTagStockInitialFilters();
};

/**
 * LOAD WAREHOUSE
 */
function loadTagStockInitialFilters() {
    $.get("/oracle-fisik/tagstock/init-filters", function (res) {
        let whSelect = $("#tag-filter-wh");

        if (!res || res.status !== "success") return;

        whSelect.html('<option value="">⚠️ PILIH GUDANG</option>');

        res.warehouses.forEach((wh) => {
            whSelect.append(`<option value="${wh}">${wh}</option>`);
        });
    });
}

/**
 * CHANGE WAREHOUSE → LOAD OPERATOR
 */
$(document)
    .off("change", "#tag-filter-wh")
    .on("change", "#tag-filter-wh", function () {
        let wh = $(this).val();
        let opSelect = $("#tag-filter-operator");

        opSelect.val("").prop("disabled", true);

        $("#tag-filter-doc-start").html(
            '<option value="">-- DOC AWAL --</option>',
        );
        $("#tag-filter-doc-end").html(
            '<option value="">-- DOC AKHIR --</option>',
        );

        $("#tbody-tagstock-rows").html(`
        <tr><td colspan="8" class="text-center">Pilih operator...</td></tr>
    `);

        $("#tfoot-tagstock-summary").addClass("d-none");

        if (!wh) return;

        $.get(
            "/oracle-fisik/tagstock/operators?warehouse=" + wh,
            function (res) {
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
            },
        );
    });

/**
 * CHANGE OPERATOR
 */
$(document)
    .off("change", "#tag-filter-operator")
    .on("change", "#tag-filter-operator", function () {
        let opId = $(this).val();

        // --- LOGIKA HIDE/SHOW FILTER DOC ---
        if (opId) {
            $("#doc-filter-container").removeClass("d-none"); // Munculkan filter
            loadTagStockData();
            loadDocFilter();
        } else {
            $("#doc-filter-container").addClass("d-none"); // Sembunyikan kalau operator dikosongkan
        }
    });

/**
 * LOAD DOC FILTER (DARI DATA YANG SAMA)
 */
function loadDocFilter() {
    $.post(
        "/oracle-fisik/tagstock/process-rows",
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

/**
 * CHANGE DOC FILTER
 */
$(document)
    .off("change", "#tag-filter-doc-start, #tag-filter-doc-end")
    .on("change", "#tag-filter-doc-start, #tag-filter-doc-end", function () {
        loadTagStockData();
    });

/**
 * MAIN DATA LOADER
 */
function loadTagStockData() {
    let wh = $("#tag-filter-wh").val();
    let opId = $("#tag-filter-operator").val();
    let docStart = $("#tag-filter-doc-start").val();
    let docEnd = $("#tag-filter-doc-end").val();

    // --- VALIDASI ANTI-KEBALIK ---
    if (docStart && docEnd && docStart > docEnd) {
        Swal.fire({
            type: "error",
            title: "Waduh...",
            text: "Doc Awal nggak boleh lebih besar dari Doc Akhir bro!",
        });
        return; // Hentikan proses jika kebalik
    }

    if (!wh || !opId) return;

    $("#tbody-tagstock-rows").html(`
        <tr>
            <td colspan="8" class="text-center">
                Loading...
            </td>
        </tr>
    `);

    $.post(
        "/oracle-fisik/tagstock/process-rows",
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
                <tr>
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
                $("#tbody-tagstock-rows").html(`
                <tr><td colspan="8" class="text-center">Tidak ada data</td></tr>
            `);
                return;
            }

            $("#tbody-tagstock-rows").html(html);

            $("#total-summary-rack").text(totalRak);
            $("#total-summary-qty").text(totalQty.toLocaleString("id-ID"));

            $("#tfoot-tagstock-summary").removeClass("d-none");
            $("#btn-print-massal-tag").removeClass("d-none");
        },
    );
}

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
        if (txt.includes("ringkasan") || txt.includes("penugasan")) {
            tr.remove();
        }
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
        <td colspan="5" style="border:1px solid #000; text-align:center;">
            GRAND TOTAL
        </td>
        <td style="border:1px solid #000; text-align:center;">
            ${$("#total-summary-rack").text() || 0}
        </td>
        <td style="border:1px solid #000; text-align:center;">
            ${$("#total-summary-qty").text() || 0}
        </td>
        <td style="border:1px solid #000;"></td>
    `;

    const tbody = cloned.querySelector("tbody");

    if (tbody) {
        tbody.appendChild(grandRow);
    }

    const tableHTML = cloned.outerHTML;

    const win = window.open("", "PRINT", "width=1200,height=800");

    if (!win) {
        alert("Popup blocked browser!");
        return;
    }

    win.document.write(`
<html>
<head>
<title>Print Tag Stock</title>

<style>
    @page { size: A4 portrait; margin: 8mm; }
    body { font-family: "Times New Roman", serif; font-size: 12px; }

    /* Judul di Tengah */
    .header { text-align: center; margin-bottom: 20px; }
    .header h2 { margin: 0; padding: 0; }

    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    th, td { border: 1px solid #000; padding: 5px; }
    th { background: #eee; text-align: center; }

    /* Rata Kanan untuk kolom Qty dan Rak */
    /* Index 5 = Jumlah Rak, Index 6 = Qty */
    td:nth-child(6), td:nth-child(7) { text-align: right; }

    /* Grand Total Rata Tengah */
    .grand-total-row td { text-align: center !important; font-weight: bold; }

    tr { page-break-inside: avoid; }
</style>

</head>
<body>
    <div class="header">
        <h2>Monitoring Stock (${wh})</h2>
        <div style="text-align: left; display: inline-block;">
            <b></b>${pic} || <b></b>${gedung} Lot ${lotRange}
        </div>
    </div>
    ${tableHTML}
</body>
</html>
`);

    win.document.close();
    win.focus();

    setTimeout(() => {
        win.print();
    }, 300);
}

window.openTagStockPrintEngine = function () {
    window.generateTagStockPrint();
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

        // --- VALIDASI ANTI-KEBALIK (TARUH DI ATAS SEBELUM WINDOW.OPEN) ---
        if (docStart && docEnd && docStart > docEnd) {
            Swal.fire({
                type: "error", // Bisa ganti 'warning' sesuai selera
                title: "Dokumen Terbalik!",
                text: "Doc Awal nggak boleh lebih besar dari Doc Akhir bro!",
                confirmButtonColor: "#3085d6",
            });
            return; // STOP di sini, jangan buka tab
        }

        let url =
            "/oracle-fisik/tagstock/print" +
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
    // 1. Reset nilai dropdown ke default
    $("#tag-filter-wh").val("").trigger("change"); // Ini bakal memicu event change untuk reset operator

    // 2. Kosongkan dropdown dokumen
    $("#tag-filter-doc-start").html('<option value="">-- DOC AWAL --</option>');
    $("#tag-filter-doc-end").html('<option value="">-- DOC AKHIR --</option>');

    // 3. Sembunyikan container filter dokumen
    $("#doc-filter-container").addClass("d-none");

    // 4. Kosongkan tabel hasil
    $("#tbody-tagstock-rows").html(`
        <tr>
            <td colspan="8" class="text-center text-muted py-5 border-0">
                Filter sudah di-reset. Silakan pilih kembali gudang dan operator bro.
            </td>
        </tr>
    `);

    // 5. Sembunyikan summary & tombol print massal
    $("#tfoot-tagstock-summary").addClass("d-none");
    $("#btn-print-massal-tag").addClass("d-none");
    if (typeof lucide !== "undefined") {
        lucide.createIcons();
    }
};
