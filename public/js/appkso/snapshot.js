window.initAppksoSnapshotMenu = function () {
    console.log("🔥 Appkso Snapshot Module Loaded bro!");

    // Load dropdown so_name dari cntso
    window.loadAppksoSnapshotFilters();

    // Event ganti nama file
    $(document)
        .off("change", "#appkso-file-excel")
        .on("change", "#appkso-file-excel", function () {
            let filename = this.files[0]
                ? this.files[0].name
                : "Klik atau seret file Excel ke sini";
            $("#appkso-text-file-excel").text(filename);
        });

    // 🎯 FORM SUBMIT IMPORT EXCEL
    $("#appkso-form-upload-snapshot")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();

            if (document.activeElement) {
                document.activeElement.blur();
            }

            let soName = $("#appkso-upload-target-so").val();
            if (!soName) {
                Swal.fire({
                    title: "Target Kosong!",
                    text: "Pilih SO Name tujuan upload terlebih dahulu bro!",
                    confirmButtonColor: "#198754",
                });
                return;
            }

            let fileInput = document.getElementById("appkso-file-excel");
            if (fileInput.files.length === 0) {
                Swal.fire({
                    title: "Berkas Kosong!",
                    text: "File Excel Snapshot belum lu pilih!",
                    confirmButtonColor: "#198754",
                });
                return;
            }

            Swal.fire({
                title: "Membongkar Berkas Excel",
                html: "Sistem sedang menyisir data lokal berkas lu... <br><strong>Mohon tunggu sejenak!</strong>",
                allowOutsideClick: false,
                didOpen: () => {
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
                                    }
                                );
                                sheetRows.push(rowData);
                            }
                        );

                        if (sheetRows.length <= 1) {
                            Swal.fire({
                                title: "Impor Gagal",
                                text: "Struktur isi berkas Excel kosong bro!",
                                confirmButtonColor: "#198754",
                            });
                            return;
                        }

                        Swal.close();
                        Swal.fire({
                            title: "Menyuntik Database",
                            html:
                                "Sedang membilas data lama & menyuntikkan data Snapshot baru...<br><strong>SO Target: " +
                                soName + "</strong>",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        $.ajax({
                            url: "/appkso/snapshot/import",
                            type: "POST",
                            data: JSON.stringify({
                                so_name: soName,
                                excel_data: sheetRows,
                            }),
                            contentType: "application/json",
                            dataType: "json",
                            headers: {
                                "X-CSRF-TOKEN": $(
                                    'meta[name="csrf-token"]'
                                ).attr("content"),
                            },
                            success: function (res) {
                                Swal.close();
                                if (res.success) {
                                    Swal.fire({
                                        title: "MANTAP KILAT!",
                                        text: res.message,
                                        confirmButtonColor: "#198754",
                                        timer: 3500,
                                    });

                                    $("#appkso-form-upload-snapshot")[0].reset();
                                    $("#appkso-text-file-excel").text(
                                        "Klik atau seret file Excel ke sini"
                                    );

                                    // Refresh tabel setelah import
                                    window.loadAppksoSnapshotFilters();
                                    setTimeout(() => {
                                        $("#appkso-filter-so")
                                            .val(soName)
                                            .trigger("change");
                                        window.loadAppksoSnapshotData();
                                    }, 500);
                                } else {
                                    Swal.fire({
                                        title: "Gagal!",
                                        text: res.message,
                                        confirmButtonColor: "#d33",
                                    });
                                }
                            },
                            error: function (xhr) {
                                Swal.close();
                                let errMsg = xhr.responseJSON
                                    ? xhr.responseJSON.message
                                    : "Gagal memproses berkas server.";
                                Swal.fire({
                                    title: "Import Gagal!",
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
                            confirmButtonColor: "#198754",
                        });
                    });
            };

            reader.readAsArrayBuffer(file);
        });
};

// 🎯 LOAD DROPDOWN SO NAME DARI CNTSO
window.loadAppksoSnapshotFilters = function () {
    let filterSoSelect = $("#appkso-filter-so");
    let uploadSoSelect = $("#appkso-upload-target-so");

    $.get("/appkso/snapshot/get-so-names", function (res) {
        if (res.success) {
            filterSoSelect.html(
                '<option value="" selected>⚠️ PILIH SO NAME</option>'
            );
            uploadSoSelect.html(
                '<option value="" disabled selected>-- PILIH SO NAME TARGET --</option>'
            );

            res.so_names.forEach((so) => {
                filterSoSelect.append(
                    `<option value="${so}">${so.toUpperCase()}</option>`
                );
                uploadSoSelect.append(
                    `<option value="${so}">${so.toUpperCase()}</option>`
                );
            });

            window.loadAppksoSnapshotData();
        }
    });
};

// 🎯 LOAD DATA TABEL SNAPSHOT
window.loadAppksoSnapshotData = function () {
    let soName = document.getElementById("appkso-filter-so").value;
    let search = document.getElementById("appkso-search-item").value;
    let tbody = document.getElementById("appkso-tbody-snapshot");

    // Reset summary
    $("#appkso-summary-total-rows").text("0");
    $("#appkso-summary-total-qty").text("0 PCS");

    if (!soName || soName === "") {
        tbody.innerHTML =
            '<tr><td colspan="5" class="text-center text-muted fw-bold py-5">⚠️ Silakan pilih SO Name terlebih dahulu untuk menampilkan data.</td></tr>';
        return;
    }

    tbody.innerHTML =
        '<tr><td colspan="5" class="text-center text-muted py-5 fw-bold"><div class="spinner-border spinner-border-sm text-success"></div> Memuat Snapshot Database...</td></tr>';

    fetch(
        `/appkso/snapshot/data?so_name=${encodeURIComponent(soName)}&search=${encodeURIComponent(search)}`,
        {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        }
    )
        .then((res) => res.json())
        .then((res) => {
            if (res.success && res.data.length > 0) {
                let html = "";
                let totalQty = 0;
                let totalRows = res.data.length;

                res.data.forEach((row, index) => {
                    let qtyInt = parseInt(row.qty) || 0;
                    totalQty += qtyInt;

                    let description = row.description
                        ? row.description
                        : '<i class="text-danger fw-bold">Item Not Found in Master Size DB</i>';

                    html += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td class="fw-bold text-dark font-monospace">${row.item}</td>
                            <td class="text-muted fw-bold">${description}</td>
                            <td class="text-end font-monospace fw-bold text-success" style="font-size: 13px;">${qtyInt.toLocaleString("id-ID")}</td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;

                $("#appkso-summary-total-rows").text(
                    totalRows.toLocaleString("id-ID")
                );
                $("#appkso-summary-total-qty").text(
                    totalQty.toLocaleString("id-ID") + " PCS"
                );
            } else {
                tbody.innerHTML =
                    '<tr><td colspan="5" class="text-center text-muted fw-bold py-5">Data Snapshot kosong / tidak ditemukan bro.</td></tr>';
            }

            if (typeof lucide !== "undefined") lucide.createIcons();
        })
        .catch((err) => {
            console.error("Gagal get data snapshot", err);
            tbody.innerHTML =
                '<tr><td colspan="5" class="text-center text-danger py-5 fw-bold">Gagal memuat data tabel.</td></tr>';
        });
};

// Auto run saat diload via AJAX Partial Blade
window.initAppksoSnapshotMenu();
