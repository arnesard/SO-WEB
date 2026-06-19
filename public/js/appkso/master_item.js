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

    const modalSimilarEl = document.getElementById("modalItemSimilar");
    if (modalSimilarEl) {
        modalSimilar = new bootstrap.Modal(modalSimilarEl, {
            backdrop: 'static',
            keyboard: false
        });
    } else {
        console.error("Modal #modalItemSimilar tidak ditemukan di DOM!");
    }

    fetchMasterData();
    fetchItemCodeList();
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
        const url = id ? `/appkso/master-update/${id}` : "/appkso/master-store";

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
            fetch(`/appkso/master-delete/${id}`, {
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

// =============================================================
// LOGIKA ITEM SIMILAR (NEW)
// =============================================================
let cachedSimilarData = [];
let cachedItemCodeList = [];
let modalSimilar = null;
let currentSimilarId = null;

/**
 * Preload list item_code dari master_items untuk autocomplete datalist
 */
function fetchItemCodeList() {
    fetch(window.appRoutes.similar_itemcode_list)
        .then((r) => r.json())
        .then((data) => {
            cachedItemCodeList = data;
            buildItemCodeDatalist(data);
        });
}

function buildItemCodeDatalist(data) {
    // Hanya isi datalist untuk MAIN saja
    const dl = document.getElementById("datalist-itemcode-main");
    if (!dl) return;
    dl.innerHTML = data
        .map(
            (d) =>
                `<option value="${d.item_code_desc}">
                    ${d.item_code_desc}${d.description ? ' — ' + d.description : ''}
                </option>`,
        )
        .join("");
}

/**
 * Buka modal Item Similar (mode: tambah baru)
 */
function openSimilarModal() {
    currentSimilarId = null;
    document.getElementById("formItemSimilar").reset();
    document.getElementById("s_similar_id").value = "";
    document.getElementById("similarModalTitle").innerText = "Tambah Item Similar";
    clearSimilarPreview("main");
    clearSimilarPreview("similar");
    fetchSimilarData(); // refresh tabel di modal
    modalSimilar.show();
}

/**
 * Ambil & render tabel similar di dalam modal
 */
function fetchSimilarData() {
    const tbody = document.getElementById("similarTableBody");
    tbody.innerHTML =
        '<tr><td colspan="9" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

    fetch(window.appRoutes.similar_list)
        .then((r) => r.json())
        .then((data) => {
            cachedSimilarData = data;
            renderSimilarTable(data);
        });
}

function renderSimilarTable(data) {
    const tbody = document.getElementById("similarTableBody");
    const countEl = document.getElementById("similarRowCountInfo");

    if (data.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data similar</td></tr>';
        if (countEl) countEl.innerText = "Total: 0 Pasang";
        return;
    }

    tbody.innerHTML = data
        .map(
            (row, i) => `
        <tr class="align-middle" style="font-size: 11px;">
            <td class="text-center text-muted bg-light" style="font-size:10px;">${i + 1}</td>
            <td class="fw-bold">${row.ItemCode || "-"}</td>
            <td class="fw-bold text-primary">${row.ItemCodeSimilar || "-"}</td>
            <td class="text-center text-muted" style="font-size:10px;">
                ${row.updated_at ? row.updated_at.substring(0, 10) : "-"}
            </td>
            <td class="text-center">
                <div class="btn-group">
                    <button class="btn btn-xs text-warning border-0 bg-transparent p-1"
                        onclick="editSimilar(${row.id})" title="Edit">
                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                    </button>
                    <button class="btn btn-xs text-danger border-0 bg-transparent p-1"
                        onclick="deleteSimilar(${row.id})" title="Hapus">
                        <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                    </button>
                </div>
            </td>
        </tr>
        `,
        )
        .join("");

    if (typeof lucide !== "undefined") lucide.createIcons();
    if (countEl) countEl.innerText = `Total: ${data.length} Pasang`;
}

/**
 * Filter tabel similar
 */
function filterSimilarTable() {
    const val = document.getElementById("searchSimilar").value.toLowerCase();
    const filtered = cachedSimilarData.filter(
        (r) =>
            (r.ItemCode && r.ItemCode.toLowerCase().includes(val)) ||
            (r.ItemCodeSimilar && r.ItemCodeSimilar.toLowerCase().includes(val)) ||
            (r.desc_main && r.desc_main.toLowerCase().includes(val)) ||
            (r.desc_similar && r.desc_similar.toLowerCase().includes(val)),
    );
    renderSimilarTable(filtered);
}

/**
 * Preview info item saat pilih dari datalist
 */
function onItemCodeChange(inputId, previewSide) {
    // Hanya preview untuk sisi main
    if (previewSide !== "main") return;

    const val = document.getElementById(inputId).value.trim();
    const found = cachedItemCodeList.find(
        (d) => d.item_code_desc === val,
    );
    showSimilarPreview(previewSide, found);
}

function showSimilarPreview(side, item) {
    const el = document.getElementById(`preview-${side}`);
    if (!el) return;
    if (!item) {
        el.innerHTML = `<span class="text-muted fst-italic" style="font-size:10px;">Pilih item code dari daftar...</span>`;
        return;
    }
    el.innerHTML = `
        <span class="badge bg-dark me-1" style="font-size:9px;">${item.grade || "-"}</span>
        <span class="badge bg-primary me-1" style="font-size:9px;">${item.product || "-"}</span>
        <span class="badge bg-info text-dark me-1" style="font-size:9px;">${item.type || "-"}</span>
        <span class="badge bg-warning text-dark me-1" style="font-size:9px;">${item.brand || "-"}</span>
        <small class="text-muted">${item.item_code || ""} — ${item.description || ""}</small>
    `;
}

function clearSimilarPreview(side) {
    const el = document.getElementById(`preview-${side}`);
    if (el) el.innerHTML = `<span class="text-muted fst-italic" style="font-size:10px;">Ketik ItemCode yang valid...</span>`;
}

/**
 * Submit form tambah/edit similar
 */
const formSimilar = document.getElementById("formItemSimilar");
if (formSimilar) {
    formSimilar.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = document.getElementById("btnSaveSimilar");
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

        const id = document.getElementById("s_similar_id").value;
        const url = id
            ? `/appkso/similar-update/${id}`
            : "/appkso/similar-store";

        fetch(url, {
            method: "POST",
            body: new FormData(this),
            headers: { "X-Requested-With": "XMLHttpRequest" },
        })
            .then((r) => r.json())
            .then((res) => {
                if (res.success) {
                    Swal.fire("Berhasil!", "Data Similar tersimpan.", "success");
                    document.getElementById("formItemSimilar").reset();
                    document.getElementById("s_similar_id").value = "";
                    document.getElementById("similarModalTitle").innerText = "Tambah Item Similar";
                    currentSimilarId = null;
                    clearSimilarPreview("main");
                    clearSimilarPreview("similar");
                    fetchSimilarData();
                } else {
                    Swal.fire("Gagal!", res.message, "error");
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save me-1"></i> SIMPAN SIMILAR';
            });
    });
}

function editSimilar(id) {
    const row = cachedSimilarData.find((x) => x.id == id);
    if (!row) return;

    currentSimilarId = id;
    document.getElementById("s_similar_id").value = id;
    document.getElementById("s_item_code").value = row.ItemCode;       // item_code_desc
    document.getElementById("s_item_code_similar").value = row.ItemCodeSimilar; // free text
    document.getElementById("similarModalTitle").innerText = "Edit Item Similar";

    // Preview hanya untuk main
    const foundMain = cachedItemCodeList.find(
        (d) => d.item_code_desc === row.ItemCode,
    );
    showSimilarPreview("main", foundMain);
    clearSimilarPreview("similar");
}

function deleteSimilar(id) {
    Swal.fire({
        title: "Hapus Relasi Similar?",
        text: "Data ini akan dihapus permanen!",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.value) {
            fetch(`/appkso/similar-delete/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
            })
                .then((r) => r.json())
                .then((res) => {
                    if (res.success) {
                        Swal.fire("Terhapus!", "Relasi similar berhasil dihapus.", "success");
                        fetchSimilarData();
                    } else {
                        Swal.fire("Gagal!", res.message, "error");
                    }
                });
        }
    });
}

/**
 * Reset form similar ke mode tambah baru
 */
function resetSimilarForm() {
    document.getElementById("formItemSimilar").reset();
    document.getElementById("s_similar_id").value = "";
    document.getElementById("similarModalTitle").innerText = "Tambah Item Similar";
    currentSimilarId = null;
    clearSimilarPreview("main");
    clearSimilarPreview("similar");
}
