window.initOracleSnapshotMenu = function () {
    console.log("🔥 Oracle Snapshot Module Loaded bro!");

    // Load filter dropdown gudang dulu
    window.loadSnapshotViewFilters();

    // Event ganti nama file di kotak upload
    $(document)
        .off("change", "#file-excel")
        .on("change", "#file-excel", function () {
            let filename = this.files[0]
                ? this.files[0].name
                : "Klik atau seret file Excel ke sini";
            $("#text-file-excel").text(filename);
        });

    // 🎯 LOGIKA FORM SUBMIT IMPORT EXCEL (Bongkar di Browser)
    $("#form-upload-snapshot")
        .off("submit")
        .on("submit", function (e) {
            e.preventDefault();

            if (document.activeElement) {
                document.activeElement.blur();
            }

            let uploadWh = $("#upload-target-wh").val();
            if (!uploadWh) {
                Swal.fire({
                    title: "Target Kosong!",
                    text: "Pilih gudang tujuan upload terlebih dahulu bro!",
                    confirmButtonColor: "#198754",
                });
                return;
            }

            let fileInput = document.getElementById("file-excel");
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
                                confirmButtonColor: "#198754",
                            });
                            return;
                        }

                        // 🎯 TANGKAP 1 ITEM SAMPEL BUAT VALIDASI GUDANG DI SERVER
                        let sampleItem = "";
                        if (sheetRows[1] && sheetRows[1][0]) {
                            sampleItem = String(sheetRows[1][0]).trim();
                        }

                        $(".swal2-title").text("Menyuntik Database");
                        $(".swal2-html-container").html(
                            "Sedang membilas data lama & menyuntikkan data Snapshot baru...<br><strong>Gudang Target: " +
                                uploadWh +
                                "</strong>",
                        );

                        $.ajax({
                            url: "/oracle-fisik/snapshot/import",
                            type: "POST",
                            data: JSON.stringify({
                                warehouse: uploadWh,
                                excel_data: sheetRows,
                                sample_item: sampleItem, // Kirim sampel buat validasi
                            }),
                            contentType: "application/json",
                            dataType: "json",
                            headers: {
                                "X-CSRF-TOKEN": $(
                                    'meta[name="csrf-token"]',
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

                                    $("#form-upload-snapshot")[0].reset();
                                    $("#text-file-excel").text(
                                        "Klik atau seret file Excel ke sini",
                                    );

                                    // Refresh dropdown gudang & load tabelnya
                                    window.loadSnapshotViewFilters();
                                    setTimeout(() => {
                                        $("#filter-snapshot-wh")
                                            .val(uploadWh)
                                            .trigger("change");
                                        window.loadSnapshotData();
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
                            confirmButtonColor: "#198754",
                        });
                    });
            };

            reader.readAsArrayBuffer(file);
        });
};

// 🎯 FUNGSI LOAD GUDANG DROPDOWN DARI DB
window.loadSnapshotViewFilters = function () {
    let viewWhSelect = $("#filter-snapshot-wh");
    $.get("/oracle-fisik/snapshot/get-warehouses", function (res) {
        if (res.success) {
            viewWhSelect.html(
                '<option value="" selected>⚠️ PILIH GUDANG</option>',
            );
            res.warehouses.forEach((wh) => {
                viewWhSelect.append(
                    `<option value="${wh}">${wh.toUpperCase()}</option>`,
                );
            });
            // Panggil loadSnapshotData untuk memunculkan teks "Wajib pilih gudang"
            window.loadSnapshotData();
        }
    });
};

// 🎯 FUNGSI AMBIL DATA TABEL & HITUNG TOTAL
window.loadSnapshotData = function () {
    let wh = document.getElementById("filter-snapshot-wh").value;
    let search = document.getElementById("search-snapshot-item").value;
    let tbody = document.getElementById("tbody-snapshot");

    // Reset Total Data ke 0 pas mulai ngeload
    $("#summary-total-rows").text("0");
    $("#summary-total-qty").text("0 PCS");

    // 🛑 LOGIKA BARU: HARUS PILIH GUDANG DULU 🛑
    if (!wh || wh === "") {
        tbody.innerHTML =
            '<tr><td colspan="5" class="text-center text-muted fw-bold py-5">⚠️ Silakan pilih target gudang di atas terlebih dahulu untuk menampilkan data.</td></tr>';
        return; // Berhenti di sini, JANGAN tembak request ke server
    }

    tbody.innerHTML =
        '<tr><td colspan="5" class="text-center text-muted py-5 fw-bold"><div class="spinner-border spinner-border-sm text-success"></div> Memuat Snapshot Database...</td></tr>';

    fetch(`/oracle-fisik/snapshot/data?warehouse=${wh}&search=${search}`, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
        },
    })
        .then((res) => res.json())
        .then((res) => {
            if (res.success && res.data.length > 0) {
                let html = "";
                let totalQty = 0;
                let totalRows = res.data.length;

                res.data.forEach((row, index) => {
                    let qtyInt = parseInt(row.qty) || 0;
                    totalQty += qtyInt; // Akumulasi Qty

                    let description = row.description
                        ? row.description
                        : '<i class="text-danger fw-bold">Item Not Found in Master Size DB</i>';
                    html += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center"><span class="badge bg-secondary">${row.warehouse}</span></td>
                    <td class="fw-bold text-dark font-monospace">${row.item}</td>
                    <td class="text-muted fw-bold">${description}</td>
                    <td class="text-end font-monospace fw-bold text-success" style="font-size: 13px;">${qtyInt.toLocaleString("id-ID")}</td>
                </tr>
                `;
                });
                tbody.innerHTML = html;

                // 🎯 UPDATE FOOTER TOTAL YANG DI BAWAH (STICKY) 🎯
                $("#summary-total-rows").text(
                    totalRows.toLocaleString("id-ID"),
                );
                $("#summary-total-qty").text(
                    totalQty.toLocaleString("id-ID") + " PCS",
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
window.initOracleSnapshotMenu();
