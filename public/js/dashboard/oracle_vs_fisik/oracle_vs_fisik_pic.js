/**
 * ⚡ MODUL EXCLUSIVE LAB: MANAJEMEN MASTER PIC & AUDITOR GUDANG BAN ⚡
 */

window.cachedStockTeamData = [];
window.cachedAuditorTeamData = [];
window.currentActiveRoleTab = "STOCK"; // Default role awal

window.initMasterPicMenu = function () {
    window.loadMasterPicDataFromServer();
};

// 1. Tarik Data Terpusat via AJAX
window.loadMasterPicDataFromServer = function () {
    let tbody = $("#tbody-master-pic-rows");
    if (!tbody || tbody.length === 0) return;

    tbody.html(
        '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div> Menyisir database personil...</td></tr>',
    );

    $.get("/oracle-fisik/pic/data", function (response) {
        if (response.status === "success") {
            window.cachedStockTeamData = response.stock_team || [];
            window.cachedAuditorTeamData = response.auditor_team || [];
            window.renderMasterPicTableHtml();
        }
    }).fail(function () {
        tbody.html(
            '<tr><td colspan="7" class="text-center text-danger py-4">Gagal sinkronisasi data personil gudang.</td></tr>',
        );
    });
};

// 2. Render Baris HTML Berbasis Memori Cache + Filter Gudang (Mendukung Semua Gudang)
window.renderMasterPicTableHtml = function () {
    let tbody = $("#tbody-master-pic-rows");
    let filterWh = $("#filter-view-pic-wh").val();
    if (!tbody || tbody.length === 0) return;

    let html = "";
    let loopIndex = 1;
    let targetDataset =
        window.currentActiveRoleTab === "STOCK"
            ? window.cachedStockTeamData
            : window.cachedAuditorTeamData;

    targetDataset.forEach((row) => {
        // 🎯 KUNCI UTAMA: Jika filterWh kosong (""), variabel matchWh akan otomatis bernilai TRUE untuk semua baris!
        let matchWh =
            !filterWh ||
            filterWh === "" ||
            row.warehouse.toLowerCase() === filterWh.toLowerCase();

        if (matchWh) {
            html += `
                <tr>
                    <td class="text-center font-monospace text-muted py-2">${loopIndex}</td>
                    <td class="text-center fw-bold text-secondary">${row.warehouse}</td>
                    <td class="text-center fw-bold text-dark font-monospace">${row.no_penneng}</td>
                    <td class="fw-bold text-dark">${row.nama}</td>
                    <td class="text-muted">${row.gedung}</td>
                    <td class="text-primary fw-bold">${row.lot}</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-xs btn-outline-warning py-1 px-2 d-inline-flex align-items-center" title="Edit Personel" onclick="window.triggerEditMasterPicMode(${row.id}, '${row.warehouse}', '${row.no_penneng}', '${row.nama}', '${row.gedung}', '${row.lot}')">
                                <i data-lucide="pencil" style="width: 11px; height: 11px;"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-danger py-1 px-2 d-inline-flex align-items-center" title="Hapus Personel" onclick="window.triggerDeleteMasterPic(${row.id})">
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
        let whText = filterWh
            ? "Gudang " + filterWh.toUpperCase()
            : "Semua Gudang";
        html = `<tr><td colspan="7" class="text-center text-muted py-4">Belum ada Personel [TIM ${window.currentActiveRoleTab}] yang terdaftar di area ${whText} bro.</td></tr>`;
    }

    tbody.html(html);
    if (window.lucide) window.lucide.createIcons(); // Selalu render ulang ikon Lucide
};

// Switch Tab Antara Tim Penghitung (Stock) vs Tim Pemeriksa (Auditor)
window.switchMasterPicTab = function (role) {
    window.currentActiveRoleTab = role;

    if (role === "STOCK") {
        $("#tab-btn-stock-kru")
            .removeClass("btn-outline-dark")
            .addClass("btn-dark");
        $("#tab-btn-auditor-kru")
            .removeClass("btn-dark")
            .addClass("btn-outline-dark");
    } else {
        $("#tab-btn-auditor-kru")
            .removeClass("btn-outline-dark")
            .addClass("btn-dark");
        $("#tab-btn-stock-kru")
            .removeClass("btn-dark")
            .addClass("btn-outline-dark");
    }

    window.renderMasterPicTableHtml();
};

// Kosongkan form isian input kembali ke awal
window.clearMasterPicForm = function () {
    $("#pic-entry-id").val("");
    $("#pic-warehouse").val("");
    $("#pic-penneng").val("");
    $("#pic-nama").val("");
    $("#pic-gedung").val("");
    $("#pic-lot").val("");
    $("#btn-cancel-edit-pic").addClass("d-none");

    $("#btn-save-pic-personel")
        .removeClass("btn-success")
        .addClass("btn-primary")
        .html(
            '<i data-lucide="save" class="me-1" style="width: 14px; height: 14px; vertical-align: middle;"></i> Simpan Personel',
        );

    if (window.lucide) window.lucide.createIcons();
};

// Lempar baris data tabel ke kotak form input kiri untuk diubah
window.triggerEditMasterPicMode = function (
    id,
    wh,
    penneng,
    nama,
    gedung,
    lot,
) {
    $("#pic-entry-id").val(id);
    $("#pic-warehouse").val(wh.toUpperCase());
    $("#pic-penneng").val(penneng);
    $("#pic-nama").val(nama);
    $("#pic-gedung").val(gedung);
    $("#pic-lot").val(lot);
    $("#pic-role-type").val(window.currentActiveRoleTab);

    $("#btn-cancel-edit-pic").removeClass("d-none");

    $("#btn-save-pic-personel")
        .removeClass("btn-primary")
        .addClass("btn-success")
        .html(
            '<i data-lucide="check" class="me-1" style="width: 14px; height: 14px; vertical-align: middle;"></i> Update Personel',
        );

    if (window.lucide) window.lucide.createIcons();
};

// --- LOGIKA AJAX FORM SUBMIT (STORE & UPDATE) ---
$(document)
    .off("submit", "#form-manage-master-pic")
    .on("submit", "#form-manage-master-pic", function (e) {
        e.preventDefault();

        let payload = {
            entry_id: $("#pic-entry-id").val(),
            role_type: $("#pic-role-type").val(),
            warehouse: $("#pic-warehouse").val(),
            no_penneng: $("#pic-penneng").val(),
            nama: $("#pic-nama").val(),
            gedung: $("#pic-gedung").val(),
            lot: $("#pic-lot").val(),
        };

        Swal.fire({
            title: "Sedang Menyimpan...",
            text: "Memproses perubahan personil di database bro.",
            allowOutsideClick: false,
            onOpen: () => {
                Swal.showLoading();
            },
        });

        $.ajax({
            url: "/oracle-fisik/pic/store",
            type: "POST",
            data: payload,
            success: function (res) {
                Swal.close();
                if (res.status === "success") {
                    Swal.fire({
                        type: "success",
                        title: "Berhasil Bro!",
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    window.clearMasterPicForm();
                    window.loadMasterPicDataFromServer();
                }
            },
            error: function (xhr) {
                Swal.close();
                let errMsg = xhr.responseJSON
                    ? xhr.responseJSON.message
                    : "Terjadi kendala koneksi server.";
                Swal.fire({
                    type: "error",
                    title: "Gagal Simpan!",
                    text: errMsg,
                });
            },
        });
    });

// --- LOGIKA AJAX DELETE DATA PERSONEL VIA BUTTON TRASH ---
window.triggerDeleteMasterPic = function (id) {
    Swal.fire({
        title: "Yakin Dihapus Bro?",
        text: "Personel area ini akan dicabut total hak aksesnya dari penugasan lapangan!",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "YA, HAPUS PERMANEN",
        cancelButtonText: "BATAL",
    }).then((result) => {
        if (result.value) {
            Swal.fire({
                title: "Mencabut Akses...",
                allowOutsideClick: false,
                onOpen: () => {
                    Swal.showLoading();
                },
            });

            $.ajax({
                url:
                    "/oracle-fisik/pic/delete/" +
                    id +
                    "?role=" +
                    window.currentActiveRoleTab,
                type: "DELETE",
                success: function (res) {
                    Swal.close();
                    if (res.status === "success") {
                        Swal.fire({
                            type: "success",
                            title: "Terhapus!",
                            text: res.message,
                            timer: 1200,
                            showConfirmButton: false,
                        });
                        window.loadMasterPicDataFromServer();
                    }
                },
                error: function (xhr) {
                    Swal.close();
                    Swal.fire({
                        type: "error",
                        title: "Gagal Hapus!",
                        text: "Kendala internal SQL.",
                    });
                },
            });
        }
    });
};
