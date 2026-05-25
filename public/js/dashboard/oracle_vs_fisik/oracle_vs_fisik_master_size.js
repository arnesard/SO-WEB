/**
 * Modul Eksklusif Master Size - Terisolasi Sempurna di File JS Sendiri
 */

// Variabel global lokal penampung memory data agar filter client-side secepat kilat
window.cachedMasterData = [];

window.initMasterSizeMenu = function () {
    window.loadMasterSizeData();
};

window.loadMasterSizeData = function () {
    let tbody = $("#tbody-master-size");
    if (!tbody || tbody.length === 0) return;

    // Tampilkan animasi loading awal saat menarik data master
    tbody.html(
        '<tr><td colspan="11" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div> Sinkronisasi database master size...</td></tr>',
    );

    $.get("/oracle-fisik/master-size/data", function (response) {
        // Simpan database murni ke memori internal browser agar tidak perlu bolak-balik nembak server sql
        window.cachedMasterData = response.master_data || [];
        let filterWh = response.filter_wh || [];
        let filterGrade = response.filter_grade || [];

        // ==========================================
        // 1. GENERATE PILIHAN FILTER WAREHOUSE
        // ==========================================
        let whSelect = $("#filter-wh");
        let currentWh = whSelect.val();

        whSelect.html('<option value="">⚠️ PILIH GUDANG</option>');
        filterWh.forEach((wh) => {
            whSelect.append(`<option value="${wh}">${wh}</option>`);
        });

        // Kembalikan posisi jika sebelumnya user sudah memilih WH tertentu
        if (currentWh) {
            whSelect.val(currentWh);
        }

        // ==========================================
        // 2. GENERATE PILIHAN FILTER GRADE
        // ==========================================
        let gradeSelect = $("#filter-grade");
        let currentGrade = gradeSelect.val();

        gradeSelect.html('<option value="">ALL GRADE</option>');
        filterGrade.forEach((grade) => {
            gradeSelect.append(`<option value="${grade}">${grade}</option>`);
        });
        gradeSelect.val(currentGrade);

        // ==========================================
        // 3. LOGIKA INTERSEPTOR: CEK KONDISI SEBELUM TAMPIL
        // ==========================================
        window.renderFisikTableLogic();
    });
};

/**
 * Fungsi khusus untuk merender baris data berdasarkan status dropdown Warehouse
 */
window.renderFisikTableLogic = function () {
    let tbody = $("#tbody-master-size");
    let selectedWh = $("#filter-wh").val();

    // JIKA USER BELUM MEMILIH WAREHOUSE, TAMPILKAN NOTICE PANDUAN!
    if (!selectedWh || selectedWh === "") {
        tbody.html(`
            <tr>
                <td colspan="11" class="text-center py-5 text-muted">
                    <div class="mb-2"><i class="fa-solid fa-warehouse text-secondary" style="font-size: 28px;"></i></div>
                    <h6 class="fw-bold mb-1 text-dark" style="font-size: 13px;">Silakan Pilih Warehouse Terlebih Dahulu</h6>
                    <p class="small mb-0 text-muted" style="font-size: 11px;">Pilih salah satu lokasi gudang di atas untuk memunculkan tabel data master size.</p>
                </td>
            </tr>
        `);
        return;
    }

    // JIKA WAREHOUSE SUDAH DIPILIH, PROSES CETAK BARIS DATA LANGSUNG BERJALAN!
    let html = "";
    let loopIndex = 1;

    window.cachedMasterData.forEach((row) => {
        // Hanya cetak baris data yang warehouse-nya cocok dengan dropdown terpilih
        if (row.warehouse.toLowerCase() === selectedWh.toLowerCase()) {
            html += `
                <tr>
                    <td class="text-center font-monospace text-muted py-2">${loopIndex}</td>
                    <td class="text-center fw-bold text-secondary text-wh-cell">${row.warehouse}</td>
                    <td class="text-center fw-bold text-dark">${row.item}</td>
                    <td>${row.description}</td>
                    <td><span class="badge ${row.grade === "OE" ? "bg-success" : row.grade === "OK" ? "bg-primary" : "bg-secondary"} text-grade-cell">${row.grade}</span></td>
                    <td>${row.product}</td>
                    <td>${row.type}</td>
                    <td>${row.brand}</td>
                    <td>${row.category}</td>
                    <td class="text-success fw-bold">${row.pattern ? row.pattern : "-"}</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-xs btn-outline-warning py-1 px-2 d-inline-flex align-items-center" onclick='window.editSize(${JSON.stringify(row)})' title="Edit">
                                <i data-lucide="pencil" style="width: 11px; height: 11px;"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-danger py-1 px-2 d-inline-flex align-items-center" onclick="window.deleteSize(${row.id})" title="Hapus">
                                <i data-lucide="trash-2" style="width: 11px; height: 11px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            loopIndex++;
        }
    });

    if (html === "") {
        html = `<tr><td colspan="11" class="text-center text-muted py-4">Belum ada rekaman master size di gudang ${selectedWh.toUpperCase()} bro.</td></tr>`;
    }

    tbody.html(html);

    // Jalankan sub-filter teks dan grade real-time
    window.filterMasterSizeTable();

    if (window.lucide) {
        window.lucide.createIcons();
    }
};

window.calculateAutoGrade = function (itemCode) {
    if (!itemCode || itemCode.trim().length < 2) {
        $("#size-grade").val("");
        return;
    }

    let lastTwo = itemCode.trim().slice(-2);

    if (lastTwo === "-0") {
        $("#size-grade").val("OE");
    } else if (lastTwo === "-1") {
        $("#size-grade").val("OK");
    } else {
        $("#size-grade").val("-");
    }
};

/**
 * ⚡ GABUNGAN REVISI: Filter dinamis Grade dan Teks yang terikat dengan Warehouse ⚡
 */
window.filterMasterSizeTable = function () {
    let whValue = $("#filter-wh").val();

    // Jika Warehouse dipindah kembali ke kosong/All, picu ulang penahan instruksi halaman
    if (!whValue || whValue === "") {
        window.renderFisikTableLogic();
        return;
    }

    // Jika dipicu karena pergantian Gudang baru, cetak ulang baris gudang target dulu
    // (Mencegah tercampurnya sisa baris gudang lama di DOM browser)
    if (
        arguments.callee.caller &&
        arguments.callee.caller.arguments[0] &&
        arguments.callee.caller.arguments[0].type === "change" &&
        arguments.callee.caller.arguments[0].target.id === "filter-wh"
    ) {
        window.renderFisikTableLogic();
        return;
    }

    let gradeValue = $("#filter-grade").val().toLowerCase();
    let textValue = $("#search-master-size").val().toLowerCase();

    $("#tbody-master-size tr").each(function () {
        let row = $(this);

        // Lewati penyaringan jika baris tersebut merupakan baris notice kosong / info warehouse
        if (row.find(".text-wh-cell").length === 0) return;

        let rowGrade = row.find(".text-grade-cell").text().toLowerCase();
        let rowAllText = row.text().toLowerCase();

        let matchGrade = gradeValue === "" || rowGrade === gradeValue;
        let matchText = textValue === "" || rowAllText.indexOf(textValue) > -1;

        if (matchGrade && matchText) {
            row.show();
        } else {
            row.hide();
        }
    });
};

window.executeSaveMasterSize = function () {
    if (
        !$("#size-warehouse").val() ||
        !$("#size-item").val() ||
        !$("#size-grade").val() ||
        !$("#size-description").val()
    ) {
        Swal.fire({
            type: "warning",
            title: "Data Kurang Lengkap",
            text: "Kolom Warehouse, Item Code, Grade, dan Description wajib diisi bro!",
        });
        return;
    }

    let id = $("#size-id").val();
    let url = id
        ? `/oracle-fisik/master-size/update/${id}`
        : "/oracle-fisik/master-size/store";

    function getCookie(name) {
        let value = "; " + document.cookie;
        let parts = value.split("; " + name + "=");
        if (parts.length == 2)
            return decodeURIComponent(parts.pop().split(";").shift());
        return null;
    }

    let token =
        getCookie("XSRF-TOKEN") ||
        $("input[name='_token']").val() ||
        $('meta[name="csrf-token"]').attr("content");

    let data = {
        warehouse: $("#size-warehouse").val(),
        item: $("#size-item").val().toUpperCase(),
        description: $("#size-description").val().toUpperCase(),
        grade: $("#size-grade").val(),
        product: $("#size-product").val().toUpperCase(),
        type: $("#size-type").val().toUpperCase(),
        brand: $("#size-brand").val().toUpperCase(),
        category: $("#size-category").val().toUpperCase(),
        _token: $("input[name='_token']").val(),
    };

    Swal.fire({
        title: "Menyimpan Data...",
        text: "Harap tunggu sejenak bro.",
        allowOutsideClick: false,
        onOpen: () => {
            Swal.showLoading();
        },
    });

    $.ajax({
        url: url,
        type: "POST",
        data: data,
        dataType: "json",
        headers: {
            "X-XSRF-TOKEN": token,
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            Swal.close();
            if (res.status === "success") {
                Swal.fire({
                    type: "success",
                    title: "Berhasil",
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false,
                });
                $("#modalMasterSize").modal("hide");
                window.resetFormSize();
                window.loadMasterSizeData();
            }
        },
        error: function (xhr) {
            Swal.close();
            let errorMsg = "Gagal memproses data ke database bro.";
            if (xhr.status === 419) {
                errorMsg =
                    "Sesi keamanan Token kedaluwarsa (Error 419). Coba tekan F5 dulu bro!";
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            Swal.fire({
                type: "error",
                title: "Gagal Simpan!",
                text: errorMsg,
            });
        },
    });
};

window.editSize = function (row) {
    $("#modalTitleSize").text("Edit Master Size");
    $("#size-id").val(row.id);
    $("#size-warehouse").val(row.warehouse);
    $("#size-item").val(row.item);
    $("#size-grade").val(row.grade);
    $("#size-description").val(row.description);
    $("#size-product").val(row.product);
    $("#size-type").val(row.type);
    $("#size-brand").val(row.brand);
    $("#size-category").val(row.category);
    $("#modalMasterSize").modal("show");
};

window.resetFormSize = function () {
    $("#size-id").val("");
    $("#form-master-size")[0].reset();
    $("#modalTitleSize").text("Tambah Master Size");
    $("#size-warehouse").val("");
    $("#size-grade").val("");
    $("#size-product").val("");
    $("#size-type").val("");
    $("#size-brand").val("");
    $("#size-category").val("");
};

window.deleteSize = function (id) {
    let token =
        $("input[name='_token']").val() ||
        $('meta[name="csrf-token"]').attr("content");

    Swal.fire({
        title: "Yakin mau hapus bro?",
        text: "Data master size ini bakal hilang permanen dari database!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: `/oracle-fisik/master-size/delete/${id}`,
                type: "DELETE",
                data: { _token: token },
                success: function (res) {
                    if (res.status === "success") {
                        Swal.fire({
                            type: "success",
                            title: "Terhapus!",
                            text: res.message,
                            timer: 1200,
                            showConfirmButton: false,
                        });
                        window.loadMasterSizeData();
                    }
                },
                error: function () {
                    Swal.fire({
                        type: "error",
                        title: "Gagal Hapus!",
                        text: "Data gagal dihapus dari database bro.",
                    });
                },
            });
        }
    });
};

$(document).ready(function () {
    window.initMasterSizeMenu();
});
