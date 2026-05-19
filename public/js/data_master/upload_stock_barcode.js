/**
 * LOGIKA MONITORING STOCK BARCODE PRO (CLEAN VERSION)
 */
let currentCalDateBC = new Date();
let selectedDateBC = null;
let currentPageBC = 1;
let lastPageBC = 1;
let searchTimer = null;

function uploadStockBarcode() {
    const fileInput = document.getElementById("upload_barcode_file");
    const tglInput = document.getElementById("upload_bc_date");

    if (!fileInput.files.length || !tglInput.value) {
        Swal.fire("Peringatan", "Lengkapi file dan tanggal dulu!", "warning");
        return;
    }

    Swal.fire({
        title: "Konfirmasi",
        text: "Upload data ke tanggal " + tglInput.value + "?",
        type: "question",
        showCancelButton: true,
        confirmButtonText: "Gas!",
    }).then((result) => {
        if (result.value) processUploadBC(fileInput.files[0], tglInput.value);
    });
}

function processUploadBC(file, tgl) {
    const formData = new FormData();
    formData.append("file_barcode", file);
    formData.append("date", tgl);
    formData.append(
        "_token",
        document.querySelector('meta[name="csrf-token"]').content,
    );

    Swal.fire({
        title: "Sedang Memproses...",
        html:
            '<div class="progress mt-3" style="height: 20px;">' +
            '<div id="bc-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%;">0%</div>' +
            "</div>",
        allowOutsideClick: false,
        showConfirmButton: false,
        onOpen: () => {
            Swal.showLoading();
            let progress = 0;
            window.bcUploadInterval = setInterval(() => {
                if (progress < 90) {
                    progress += 5;
                    const pBar = document.getElementById("bc-progress-bar");
                    if (pBar) {
                        pBar.style.width = progress + "%";
                        pBar.innerText = progress + "%";
                    }
                }
            }, 1000);
        },
    });

    fetch("/data_master/monitoring-upload", { method: "POST", body: formData })
        .then((r) => r.json())
        .then((res) => {
            clearInterval(window.bcUploadInterval);
            if (res.success) {
                Swal.fire("Berhasil!", res.count + " data sinkron.", "success");
                refreshCalendarBC();
                document.getElementById("upload_barcode_file").value = "";
            } else {
                Swal.fire("Gagal!", res.message, "error");
            }
        })
        .catch(() => {
            clearInterval(window.bcUploadInterval);
            Swal.fire("Error", "Gagal!", "error");
        });
}

function renderBarcodeTable(data) {
    const tbody = document.getElementById("barcodeTableBody");
    tbody.innerHTML = "";

    if (!data || data.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="15" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>';
        return;
    }

    const fmt = (n) => new Intl.NumberFormat("id-ID").format(n || 0);

    const rows = data
        .map((row, i) => {
            const rowNumber = (currentPageBC - 1) * 100 + (i + 1);
            const holdColor = row.hold_days > 0 ? "text-danger fw-bold" : "";

            return `
            <tr class="align-middle" style="font-size: 9px;">
                <td class="text-center bg-light text-muted">${rowNumber}</td>
                <td class="fw-bold text-primary">${row.rack_code}</td>
                <td class="fw-bold">${row.item_code}</td>
                <td class="text-center">${row.whs_week} / ${row.cur_week}</td>
                <td class="text-end fw-bold text-success bg-light">${fmt(row.qty)}</td>
                <td class="text-end">${fmt(row.qc)}</td>
                <td class="text-end">${fmt(row.qa)}</td>
                <td class="text-end">${fmt(row.qaa)}</td>
                <td class="text-end">${fmt(row.rnd)}</td>
                <td class="text-end text-warning">${fmt(row.holds)}</td>
                <td class="text-end">${fmt(row.oem)}</td>
                <td class="text-end text-danger">${fmt(row.ng)}</td>
                <td class="text-center">${row.booking || "-"}</td>
                <td class="text-center bg-light">${row.loc_code || "-"}</td>
                <td class="text-center ${holdColor}">${row.hold_days}</td>
            </tr>`;
        })
        .join("");

    tbody.innerHTML = rows;
}

// Logic lain (fetch, pagination, filter) tetap sama seperti sebelumnya...
function fetchBarcodeData(ds, page, search = "") {
    const tbody = document.getElementById("barcodeTableBody");
    tbody.innerHTML =
        '<tr><td colspan="15" class="text-center py-4">Memuat data...</td></tr>';

    fetch(
        `/data_master/monitoring-list?date=${ds}&page=${page}&search=${search}`,
    )
        .then((r) => r.json())
        .then((res) => {
            lastPageBC = res.last_page;
            renderBarcodeTable(res.data);
            // Tambahkan parameter res.summary di sini
            updatePaginationUI(
                res.current_page,
                res.last_page,
                res.total,
                res.summary,
            );
        });
}

function filterBarcodeTable() {
    clearTimeout(searchTimer);
    const val = document.getElementById("searchBarcode").value;
    searchTimer = setTimeout(() => {
        if (selectedDateBC) {
            currentPageBC = 1;
            fetchBarcodeData(selectedDateBC, 1, val);
        }
    }, 600);
}

function selectDateBC(ds) {
    selectedDateBC = ds;
    currentPageBC = 1;
    document.getElementById("bc_detail_title").innerText =
        "Detail Data : " + ds;
    refreshCalendarBC();
    fetchBarcodeData(ds, 1);
}
function changeMonthBC(off) {
    currentCalDateBC.setMonth(currentCalDateBC.getMonth() + off);
    refreshCalendarBC();
}
function refreshCalendarBC() {
    const year = currentCalDateBC.getFullYear();
    const month = currentCalDateBC.getMonth() + 1;
    fetch(`/data_master/monitoring-calendar?year=${year}&month=${month}`)
        .then((r) => r.json())
        .then((activeDates) => {
            const grid = document.getElementById("calendarGridBC");
            if (!grid) return;
            grid.innerHTML = "";
            document.getElementById("calMonthTitleBC").innerText =
                currentCalDateBC
                    .toLocaleString("id-ID", { month: "long", year: "numeric" })
                    .toUpperCase();
            let firstDay = new Date(year, month - 1, 1).getDay();
            let padding = firstDay === 0 ? 6 : firstDay - 1;
            for (let i = 0; i < padding; i++)
                grid.innerHTML +=
                    '<div class="col" style="flex:0 0 14.28%; height:28px;"></div>';
            const days = new Date(year, month, 0).getDate();
            for (let d = 1; d <= days; d++) {
                const ds =
                    year +
                    "-" +
                    String(month).padStart(2, "0") +
                    "-" +
                    String(d).padStart(2, "0");
                const hasData = activeDates.includes(ds);
                let cls =
                    selectedDateBC === ds
                        ? "bg-warning text-dark border-dark"
                        : hasData
                          ? "bg-success text-white"
                          : "bg-white text-muted border-light";
                grid.innerHTML +=
                    '<div class="col" style="flex:0 0 14.28%;"><div onclick="selectDateBC(\'' +
                    ds +
                    '\')" class="' +
                    cls +
                    ' rounded-circle d-flex align-items-center justify-content-center m-auto shadow-sm" style="width:24px; height:24px; cursor:pointer; font-size:10px; font-weight:bold;">' +
                    d +
                    "</div></div>";
            }
        });
}

function updatePaginationUI(current, last, total, summary) {
    const nav = document.getElementById("bcPagination");
    const infoBox = document.getElementById("barcodeCountInfo");

    if (summary) {
        const fmt = (n) => new Intl.NumberFormat("id-ID").format(n || 0);

        // Logic hitungan sesuai request lu:
        const totalRack = summary.total_rack; // Logic 1
        const totalQty = summary.total_qty; // Logic 2
        const totalOem = summary.total_oem; // Logic 4
        const totalOk = totalQty - totalOem; // Logic 3 (Qty - OEM)

        infoBox.innerHTML = `Total: ${fmt(totalRack)} Rack, Qty: ${fmt(totalQty)} Pcs (Grade OK: ${fmt(totalOk)} Pcs & OE: ${fmt(totalOem)} Pcs)`;
    }

    if (last > 1) {
        nav.classList.remove("d-none");
        document.getElementById("bcPageInfo").innerText =
            `${current} / ${last}`;
    } else {
        nav.classList.add("d-none");
    }
}

function changePageBC(dir) {
    if (dir === "next" && currentPageBC < lastPageBC) currentPageBC++;
    else if (dir === "prev" && currentPageBC > 1) currentPageBC--;
    else return;
    fetchBarcodeData(
        selectedDateBC,
        currentPageBC,
        document.getElementById("searchBarcode").value,
    );
}

document.addEventListener("DOMContentLoaded", () => {
    refreshCalendarBC();
});
