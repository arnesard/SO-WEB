/**
 * LOGIKA MASTER DATA
 */
let cachedMasterData = [];
let modalMaster = null;

// Inisialisasi saat halaman siap
document.addEventListener("DOMContentLoaded", () => {
    const modalEl = document.getElementById("modalMasterItem");
    if (modalEl) {
        modalMaster = new bootstrap.Modal(modalEl);
    }
    // LANGSUNG PANGGIL BIAR DATA MUNCUL PAS BUKA
    fetchMasterData();
});

function fetchMasterData() {
    const tbody = document.getElementById("masterTableBody");
    tbody.innerHTML =
        '<tr><td colspan="10" class="text-center py-5"><span class="spinner-border spinner-border-sm"></span> Loading Master Data...</td></tr>';

    fetch(`${window.appRoutes.master_list}`)
        .then((r) => r.json())
        .then((data) => {
            cachedMasterData = data;
            renderMasterTable(data);
        });
}

function renderMasterTable(data) {
    const tbody = document.getElementById("masterTableBody");
    tbody.innerHTML = ""; // BERSIHKAN TOTAL BIAR GAK DOUBLE

    if (data.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data master</td></tr>';
        document.getElementById("masterRowCountInfo").innerText =
            "Total: 0 Items";
        return;
    }

    tbody.innerHTML = data
        .map(
            (row, i) => `
        <tr class="align-middle">
            <td class="text-center bg-light text-muted" style="font-size: 10px;">${i + 1}</td>
            <td class="fw-bold">${row.item_code || "-"}</td>
            <td class="text-primary fw-bold">${row.item_code_desc}</td>
            <td><small>${row.description || "-"}</small></td>
            <td class="text-center">${row.grade || "-"}</td>
            <td>${row.product || "-"}</td>
            <td>${row.type || "-"}</td>
            <td>${row.brand || "-"}</td>
            <td>${row.category || "-"}</td>
            <td class="text-center">
                <div class="btn-group">
                    <button class="btn btn-xs text-primary border-0 bg-transparent p-1" onclick="editItem(${row.id})" title="Edit">
                        <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                    </button>
                    <button class="btn btn-xs text-danger border-0 bg-transparent p-1" onclick="deleteItem(${row.id})" title="Hapus">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>
            </td>
        </tr>
    `,
        )
        .join("");

    if (typeof lucide !== "undefined") lucide.createIcons();
    document.getElementById("masterRowCountInfo").innerText =
        `Total: ${data.length} Items`;
}

function filterMasterTable() {
    const val = document.getElementById("searchMaster").value.toLowerCase();
    const filtered = cachedMasterData.filter(
        (i) =>
            (i.item_code_desc &&
                i.item_code_desc.toLowerCase().includes(val)) ||
            (i.description && i.description.toLowerCase().includes(val)) ||
            (i.item_code && i.item_code.toLowerCase().includes(val)) ||
            (i.brand && i.brand.toLowerCase().includes(val)),
    );
    renderMasterTable(filtered);
}

function openAddModal() {
    document.getElementById("formMasterItem").reset();
    document.getElementById("item_id").value = "";
    document.getElementById("modalTitle").innerText = "Tambah Item Baru";
    modalMaster.show();
}

const formMaster = document.getElementById("formMasterItem");
if (formMaster) {
    formMaster.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = document.getElementById("btnSaveMaster");
        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

        const formData = new FormData(this);
        const id = document.getElementById("item_id").value;
        const url = id
            ? `/data_master/master-update/${id}`
            : "/data_master/master-store";

        fetch(url, {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    if (
                        typeof missingQueue !== "undefined" &&
                        missingQueue.length > 0
                    ) {
                        missingQueue.shift();
                        processNextQueue();
                    } else {
                        Swal.fire(
                            "Berhasil",
                            "Data Master tersimpan!",
                            "success",
                        );
                        modalMaster.hide();
                        fetchMasterData();
                    }
                } else {
                    Swal.fire("Gagal", res.message, "error");
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML =
                    '<i class="fa-solid fa-save me-1"></i> SIMPAN DATA';
            });
    });
}

function editItem(id) {
    const item = cachedMasterData.find((x) => x.id == id);
    if (!item) return;

    document.getElementById("item_id").value = item.id;
    document.getElementById("m_item_code").value = item.item_code || "";
    document.getElementById("m_item_code_desc").value =
        item.item_code_desc || "";
    document.getElementById("m_description").value = item.description || "";
    document.getElementById("m_grade").value = item.grade || "";
    document.getElementById("m_brand").value = item.brand || "";
    document.getElementById("m_category").value = item.category || "";
    document.getElementById("m_product").value = item.product || "";
    document.getElementById("m_type").value = item.type || "";

    document.getElementById("modalTitle").innerText = "Edit Item Master";
    modalMaster.show();
}

function deleteItem(id) {
    Swal.fire({
        title: "Hapus Data?",
        text: "Data master ini akan dihapus permanen!",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus!",
    }).then((result) => {
        if (result.value) {
            fetch(`/data_master/master-delete/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
            })
                .then((r) => r.json())
                .then((res) => {
                    if (res.success) {
                        Swal.fire(
                            "Terhapus!",
                            "Data berhasil dibuang.",
                            "success",
                        );
                        fetchMasterData();
                    }
                });
        }
    });
}
