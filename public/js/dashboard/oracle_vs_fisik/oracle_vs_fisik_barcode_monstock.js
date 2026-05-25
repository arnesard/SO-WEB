/**
 * Modul Eksklusif Barcode Monstock - Terisolasi Sempurna di File JS Sendiri
 */

/**
 * Modul Eksklusif Barcode Monstock - Terisolasi Sempurna di File JS Sendiri
 */

window.cachedMonstockData = [];
window.cachedLastUpload = {}; // 🚀 Wadah global untuk menyimpan list tanggal upload

window.initBarcodeMonstockMenu = function () {
    window.loadBarcodeMonstockData();
};

window.loadBarcodeMonstockData = function () {
    let tbody = $("#tbody-barcode-monstock");
    if (!tbody.length) return;

    tbody.html(`
        <tr>
            <td colspan="8" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-secondary"></div>
                Sinkronisasi database monstock...
            </td>
        </tr>
    `);

    $.get("/oracle-fisik/barcode/data", function (response) {
        window.cachedMonstockData = response.master_data || [];
        window.cachedLastUpload = response.last_upload || {};
        let filterWh = response.filter_wh || [];

        let whSelect = $("#filter-monstock-wh");
        let currentWh = whSelect.val() || "";

        whSelect.empty();
        whSelect.append(`<option value="">⚠️ PILIH GUDANG</option>`);

        if (!Array.isArray(filterWh)) filterWh = [];

        filterWh.forEach((wh) => {
            if (!wh) return;

            let whKey = String(wh).trim().toUpperCase();
            let lastUpload = window.cachedLastUpload?.[whKey];

            let label = "";

            if (lastUpload) {
                let t = lastUpload.split(/[- :]/);
                if (t.length >= 5) {
                    label = `${wh} — Terakhir Upload: ${t[2]}/${t[1]}/${t[0]} - Jam ${t[3]}:${t[4]}`;
                } else {
                    label = `${wh} — Terakhir Upload: ${lastUpload}`;
                }
            } else {
                label = `${wh} — Belum pernah diupload`;
            }

            whSelect.append(new Option(label, wh));
        });

        if (currentWh) {
            whSelect.val(currentWh);
        }

        window.renderMonstockTableHtml();
    });
};

window.renderMonstockTableHtml = function () {
    let tbody = $("#tbody-barcode-monstock");
    let selectedWh = $("#filter-monstock-wh").val();

    if (!selectedWh || selectedWh === "") {
        // 🚀 Sembunyikan info tanggal kalau gudang belum dipilih
        $("#last-upload-container").addClass("d-none");

        tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <div class="mb-2"><i class="fa-solid fa-layer-group text-secondary" style="font-size: 26px;"></i></div>
                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 12px;">Silakan Pilih Warehouse Terlebih Dahulu</h6>
                    <p class="small mb-0 text-muted" style="font-size: 11px;">Pilih salah satu lokasi gudang di atas untuk memunculkan data monitoring stock.</p>
                </td>
            </tr>
        `);
        return;
    }

    // ⚡ LOGIKA FORMATTING TANGGAL TERAKHIR UPLOAD ⚡
    let lastUploadTime = window.cachedLastUpload[selectedWh.toUpperCase()];
    if (lastUploadTime) {
        // Pisahkan string "YYYY-MM-DD HH:MM:SS" biar rapi dibaca user Indonesia
        let t = lastUploadTime.split(/[- :]/);
        let formattedDate = `${t[2]}/${t[1]}/${t[0]} - Jam ${t[3]}:${t[4]}`;

        $("#last-upload-text").text(formattedDate);
        $("#last-upload-container").removeClass("d-none");
    } else {
        $("#last-upload-text").text("Belum pernah diupload");
        $("#last-upload-container").removeClass("d-none");
    }

    let html = "";
    let loopIndex = 1;

    window.cachedMonstockData.forEach((row) => {
        if (row.warehouse.toLowerCase() === selectedWh.toLowerCase()) {
            let deskripsi = row.description ? row.description : "-";

            html += `
                <tr>
                    <td class="text-center font-monospace text-muted py-2">${loopIndex}</td>
                    <td class="text-center fw-bold text-secondary text-wh-cell">${row.warehouse}</td>
                    <td class="fw-bold text-dark text-rack-cell">${row.rackcode}</td>
                    <td class="fw-bold text-dark text-item-cell">${row.item}</td>
                    <td class="text-muted text-desc-cell">${deskripsi}</td>
                    <td class="text-end fw-bold font-monospace text-primary">${Number(row.jml).toLocaleString("id-ID")}</td>
                    <td class="text-end fw-bold font-monospace text-success">${Number(row.oem).toLocaleString("id-ID")}</td>
                    <td class="text-muted text-center text-loc-cell">${row.loccode}</td>
                </tr>
            `;
            loopIndex++;
        }
    });

    if (html === "") {
        html = `<tr><td colspan="7" class="text-center text-muted py-4">Belum ada rekaman data barcode di gudang ${selectedWh.toUpperCase()}.</td></tr>`;
    }

    tbody.html(html);
    if (window.lucide) window.lucide.createIcons();
};

// ... Sisa fungsi filterMonstockTableLogic, reset, dan binding di bawahnya tetap biarkan utuh seperti semula ...

/**
 * ⚡ PROSES FILTER PENCARIAN MANUAL (STERIL & RINGAN 100%) ⚡
 */
window.filterMonstockTableLogic = function (isFromSearchButton = false) {
    let whValue = $("#filter-monstock-wh").val();

    if (!whValue || whValue === "") {
        window.renderMonstockTableHtml();
        return;
    }

    if (isFromSearchButton === true) {
        let textValue = $("#search-monstock-item").val().toLowerCase().trim();

        if (textValue === "") {
            window.resetMonstockSearchField();
            return;
        }

        Swal.fire({
            title: "Mencari Data...",
            text:
                "Sistem sedang melacak kode '" +
                textValue.toUpperCase() +
                "' di gudang " +
                whValue +
                " .",
            allowOutsideClick: false,
            timer: 400,
            showConfirmButton: false,
            onOpen: () => {
                Swal.showLoading();
            },
            onClose: () => {
                let totalDataKetemu = 0;

                $("#tbody-barcode-monstock tr").each(function () {
                    let row = $(this);
                    if (row.find(".text-wh-cell").length === 0) return;

                    let cellRack = row
                        .find(".text-rack-cell")
                        .text()
                        .toLowerCase();
                    let cellItem = row
                        .find(".text-item-cell")
                        .text()
                        .toLowerCase();
                    let cellDesc = row
                        .find(".text-desc-cell")
                        .text()
                        .toLowerCase();
                    let cellLoc = row
                        .find(".text-loc-cell")
                        .text()
                        .toLowerCase();

                    let matchText =
                        cellRack.indexOf(textValue) > -1 ||
                        cellItem.indexOf(textValue) > -1 ||
                        cellDesc.indexOf(textValue) > -1 ||
                        cellLoc.indexOf(textValue) > -1;

                    if (matchText) {
                        row.show();
                        totalDataKetemu++;
                    } else {
                        row.hide();
                    }
                });

                if (totalDataKetemu > 0) {
                    let visibleIndex = 1;
                    $("#tbody-barcode-monstock tr:visible").each(function () {
                        $(this).find("td:first").text(visibleIndex);
                        visibleIndex++;
                    });
                }

                if (totalDataKetemu === 0) {
                    Swal.fire({
                        type: "info",
                        title: "Data Tidak Ada !",
                        text:
                            "Kata kunci '" +
                            textValue.toUpperCase() +
                            "' gak ketemu di data barcode gudang " +
                            whValue +
                            " .",
                        confirmButtonText: "SIAP ",
                        confirmButtonColor: "#3085d6",
                    });
                }
            },
        });
    }
};

window.resetMonstockSearchField = function () {
    let whValue = $("#filter-monstock-wh").val();
    $("#search-monstock-item").val("");

    if (!whValue || whValue === "") {
        window.renderMonstockTableHtml();
        return;
    }
    window.renderMonstockTableHtml();
};

// --- BINDING EVENT DROPDOWN & FORM ---
$(document)
    .off("change", "#filter-monstock-wh")
    .on("change", "#filter-monstock-wh", function () {
        let whValue = $(this).val();
        if (!whValue || whValue === "") {
            window.renderMonstockTableHtml();
            return;
        }

        $("#search-monstock-item").val("");

        Swal.fire({
            title: "Memilah Data...",
            text: "Sedang menyaring data barcode gudang " + whValue + " .",
            allowOutsideClick: false,
            timer: 600,
            showConfirmButton: false,
            onOpen: () => {
                Swal.showLoading();
            },
            onClose: () => {
                window.renderMonstockTableHtml();
            },
        });
    });

$(document)
    .off("keypress", "#search-monstock-item")
    .on("keypress", "#search-monstock-item", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            window.filterMonstockTableLogic(true);
        }
    });

$(document)
    .off("change", "#file-csv")
    .on("change", "#file-csv", function () {
        let filename = this.files[0]
            ? this.files[0].name
            : "Klik atau seret file CSV ke sini";
        $("#text-file-csv").text(filename);
    });

$(document)
    .off("submit", "#form-upload-barcode")
    .on("submit", "#form-upload-barcode", function (e) {
        e.preventDefault();
        let targetWh = $("#upload-target-wh").val();
        if (!targetWh) {
            Swal.fire({
                type: "warning",
                title: "Gudang Belum Dipilih!",
                text: "Pilih target warehouse dulu, sebelum memproses file CSV!",
            });
            return;
        }

        let formData = new FormData(this);
        Swal.fire({
            title: "Sedang Memproses...",
            text:
                "Harap tunggu, sistem sedang membilas data lama & parsing data CSV baru ke gudang " +
                targetWh +
                " .",
            allowOutsideClick: false,
            onOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: "/oracle-fisik/barcode/import",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                Swal.close();
                if (res.status === "success") {
                    Swal.fire({
                        type: "success",
                        title: "Sukses !",
                        text: res.message,
                    });
                    $("#form-upload-barcode")[0].reset();
                    $("#text-file-csv").text(
                        "Klik atau seret file CSV ke sini",
                    );
                    window.loadBarcodeMonstockData();
                }
            },
            error: function (xhr) {
                Swal.close();
                let msg = xhr.responseJSON
                    ? xhr.responseJSON.message
                    : "Terjadi kesalahan sistem saat parsing CSV.";
                Swal.fire({ type: "error", title: "Gagal!", text: msg });
            },
        });
    });

window.initBarcodeMonstockMenu();
