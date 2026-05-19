/**
 * LOGIKA GT REPORT DAILY TRANSACTION
 */
let cachedDataDaily = [];
let currentCalDate = new Date();
let selectedDate = null;
let missingQueue = [];

// Variabel khusus Pagination Summary
let allSummaryData = [];
let currentSummaryPage = 1;
const itemsPerSummaryPage = 4;

/**
 * NAVIGASI HALAMAN (Centralized)
 */
function showSection(sectionId, btnId) {
    document
        .querySelectorAll(".content-section")
        .forEach((s) => s.classList.remove("active"));
    document.getElementById(sectionId).classList.add("active");

    const btns = ["btn-report", "btn-master", "btn-monitoring"];
    btns.forEach((id) => {
        const btn = document.getElementById(id);
        if (btn)
            btn.classList.remove(
                "active-report",
                "active-master",
                "active-monitoring",
            );
    });

    const currentBtn = document.getElementById(btnId);
    if (btnId === "btn-report") currentBtn.classList.add("active-report");
    else if (btnId === "btn-master") currentBtn.classList.add("active-master");
    else if (btnId === "btn-monitoring")
        currentBtn.classList.add("active-monitoring"); // Tambah warna hijau di CSS jika perlu

    if (btnId === "btn-monitoring") fetchBarcodeData();
}

/**
 * LOGIKA KALENDER
 */
function changeMonth(off) {
    currentCalDate.setMonth(currentCalDate.getMonth() + off);
    refreshCalendar();
}

function refreshCalendar() {
    const year = currentCalDate.getFullYear();
    const month = currentCalDate.getMonth() + 1;

    fetch(`${window.appRoutes.calendar}?year=${year}&month=${month}`)
        .then((r) => r.json())
        .then((activeDates) => {
            const grid = document.getElementById("calendarGrid");
            grid.innerHTML = "";
            document.getElementById("calMonthTitle").innerText = currentCalDate
                .toLocaleString("id-ID", { month: "long", year: "numeric" })
                .toUpperCase();

            let firstDay = new Date(year, month - 1, 1).getDay();
            let padding = firstDay === 0 ? 6 : firstDay - 1;
            for (let i = 0; i < padding; i++) {
                grid.innerHTML += `<div class="col" style="flex:0 0 14.28%; height:28px;"></div>`;
            }

            const days = new Date(year, month, 0).getDate();
            for (let d = 1; d <= days; d++) {
                const ds = `${year}-${String(month).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
                const hasData = activeDates.includes(ds);
                const isSelected = selectedDate === ds;

                let cls = isSelected
                    ? "bg-warning text-dark border-dark"
                    : hasData
                      ? "bg-success text-white"
                      : "bg-white text-muted border-light";

                grid.innerHTML += `
                    <div class="col" style="flex:0 0 14.28%;">
                        <div onclick="selectDate('${ds}')" class="${cls} rounded-circle d-flex align-items-center justify-content-center m-auto shadow-sm" style="width:24px; height:24px; cursor:pointer; font-size:10px; font-weight:bold;">
                            ${d}
                        </div>
                    </div>`;
            }
        });
}

function selectDate(ds) {
    selectedDate = ds;
    refreshCalendar();
    fetchDetailData(ds);
    fetchSummary(ds);
    checkMissingMasterData(ds);
}

function checkMissingMasterData(ds) {
    const marquee = document.getElementById("runningTextAlert");
    if (!marquee) return;

    fetch(`${window.appRoutes.check_missing}?date=${ds}`)
        .then((r) => r.json())
        .then((data) => {
            if (data.length > 0) {
                missingQueue = data; // Masukkan semua ke antrian

                let alertText = "⚠️ PENCEGATAN DATA MASTER: ";
                const itemsText = data
                    .map((item) => `[${item.item_code_desc}]`)
                    .join(" | ");
                marquee.innerHTML =
                    alertText + itemsText + " -- MOHON LENGKAPI DATA!";
                marquee.className = "fw-bold small text-danger";

                // PANGGIL SI PENCEGAT!
                processNextQueue();
            } else {
                marquee.innerHTML =
                    "✅ Semua item tanggal " + ds + " sudah Ada di Database.";
                marquee.className = "fw-bold small text-black";
            }
        });
}

function processNextQueue() {
    if (missingQueue.length === 0) {
        // Jika antrian habis, tutup modal
        const modalEl = document.getElementById("modalMasterItem");
        const inst = bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();

        // Refresh status tulisan berjalan & kalender
        if (selectedDate) checkMissingMasterData(selectedDate);
        return;
    }

    // 1. Ambil barang pertama dari antrian
    const item = missingQueue[0];
    const codeDesc = item.item_code_desc || "";

    // 2. OTOMATIS TEBAK GRADE (Logika Suffix)
    let guessedGrade = "";
    if (codeDesc.endsWith("-0")) {
        guessedGrade = "OE";
    } else if (codeDesc.endsWith("-1")) {
        guessedGrade = "OK";
    }

    // 3. Isi Form Modal secara otomatis
    document.getElementById("item_id").value = ""; // Pastikan mode Add (ID Kosong)
    document.getElementById("m_item_code_desc").value = codeDesc;
    document.getElementById("m_description").value = item.description || "";

    // Ambil angka depan saja untuk Item Code
    document.getElementById("m_item_code").value = codeDesc.split("-")[0];

    // 4. Masukkan hasil tebakan Grade ke input
    document.getElementById("m_grade").value = guessedGrade;

    // 5. Reset kolom sisanya biar bersih
    document.getElementById("m_brand").value = "";
    document.getElementById("m_category").value = "";
    document.getElementById("m_product").value = "";
    document.getElementById("m_type").value = "";

    // 6. Update Judul Modal (Biar user tau sisa berapa lagi)
    document.getElementById("modalTitle").innerText =
        `Data Baru !!! Masukan Ke Database, Total : ${missingQueue.length} Item Lagi`;

    // 7. Tampilkan Modal & Atur Fokus Kursor (Ngebut Mode)
    if (typeof modalMaster !== "undefined") {
        modalMaster.show();

        setTimeout(() => {
            // Jika Grade sudah terisi otomatis oleh sistem, kursor langsung ke Product
            // Jika gagal nebak, kursor baru ke Grade
            if (guessedGrade !== "") {
                document.getElementById("m_product").focus();
            } else {
                document.getElementById("m_grade").focus();
            }
        }, 500); // Kasih delay dikit biar animasi modal kelar dulu
    }
}

/**
 * LOGIKA TABEL DATA DETAIL
 */
function fetchDetailData(ds) {
    const tbody = document.getElementById("mainDisplayBody");
    tbody.innerHTML =
        '<tr><td colspan="18" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Memuat Data...</td></tr>';

    fetch(`${window.appRoutes.data}?date=${ds}`)
        .then((r) => r.json())
        .then((data) => {
            cachedDataDaily = data;
            renderTableContent(data);
        });
}

function renderTableContent(data) {
    const tbody = document.getElementById("mainDisplayBody");
    const fmt = (n) => new Intl.NumberFormat("id-ID").format(n || 0);

    if (data.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="18" class="text-center py-4 text-muted">Tidak ada data ditemukan</td></tr>';
        document.getElementById("rowCountInfo").innerText = "Total: 0 Data";
        return;
    }

    tbody.innerHTML = data
        .map(
            (row, i) => `
        <tr>
            <td class="text-center bg-light">${i + 1}</td>
            <td class="fw-bold">${row.item_code}</td>
            <td>${row.description}</td>
            <td class="text-end bg-light">${fmt(row.oe_stk_awal)}</td>
            <td class="text-end bg-light">${fmt(row.oe_in)}</td>
            <td class="text-end bg-light">${fmt(row.oe_out)}</td>
            <td class="text-end bg-light">${fmt(row.oe_adj)}</td>
            <td class="text-end fw-bold text-success bg-light">${fmt(row.oe_stk_akhir)}</td>
            <td class="text-end">${fmt(row.ok_stk_awal)}</td>
            <td class="text-end">${fmt(row.ok_in)}</td>
            <td class="text-end">${fmt(row.ok_out)}</td>
            <td class="text-end">${fmt(row.ok_adj)}</td>
            <td class="text-end fw-bold text-primary">${fmt(row.ok_stk_akhir)}</td>
            <td class="text-end bg-light">${fmt(row.nd_stk_awal)}</td>
            <td class="text-end bg-light">${fmt(row.nd_in)}</td>
            <td class="text-end bg-light">${fmt(row.nd_out)}</td>
            <td class="text-end bg-light">${fmt(row.nd_adj)}</td>
            <td class="text-end fw-bold text-danger bg-light">${fmt(row.nd_stk_akhir)}</td>
        </tr>`,
        )
        .join("");

    document.getElementById("rowCountInfo").innerText =
        `Total: ${data.length} Data`;
}

function filterTable() {
    const val = document.getElementById("searchData").value.toLowerCase();
    const filtered = cachedDataDaily.filter(
        (i) =>
            i.item_code.toLowerCase().includes(val) ||
            i.description.toLowerCase().includes(val),
    );
    renderTableContent(filtered);
}

/**
 * LOGIKA PROSES UPLOAD
 */
function previewTrxData(inp) {
    const btn = document.getElementById("btnSubmitTrx");
    inp.files.length > 0
        ? btn.classList.remove("disabled")
        : btn.classList.add("disabled");
}

function handleDailyUpload() {
    const fileInput = document.getElementById("file_txt_daily");
    const btnSubmit = document.getElementById("btnSubmitTrx");
    if (!fileInput.files.length) return;

    btnSubmit.disabled = true;
    btnSubmit.classList.add("disabled");
    btnSubmit.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>PROSES UPLOAD...';

    const formData = new FormData();
    formData.append("file_txt", fileInput.files[0]);
    formData.append(
        "_token",
        document.querySelector('meta[name="csrf-token"]').content,
    );

    fetch(window.appRoutes.upload, { method: "POST", body: formData })
        .then(async (res) => {
            const responseData = await res.json();
            if (!res.ok)
                throw new Error(responseData.message || "Gagal memproses file");
            return responseData;
        })
        .then((res) => {
            alert(
                "Berhasil! " + res.count + " data telah disimpan ke database.",
            );
            fileInput.value = "";
            refreshCalendar();
        })
        .catch((err) => alert("Kesalahan: " + err.message))
        .finally(() => {
            btnSubmit.disabled = false;
            btnSubmit.classList.remove("disabled");
            btnSubmit.innerHTML =
                '<i class="fa-solid fa-cloud-arrow-up me-2"></i> KONFIRMASI UPLOAD';
            if (typeof lucide !== "undefined") lucide.createIcons();
        });
}

/**
 * LOGIKA PAGINATION SUMMARY
 */
function fetchSummary(ds) {
    const sBody = document.getElementById("summaryBody");
    const pagDiv = document.getElementById("summaryPagination");
    sBody.innerHTML = '<tr><td colspan="3" class="text-center">...</td></tr>';
    pagDiv.classList.add("d-none");

    fetch(`${window.appRoutes.summary}?date=${ds}`)
        .then((r) => r.json())
        .then((data) => {
            allSummaryData = data;
            currentSummaryPage = 1;
            renderSummaryTable();
        });
}

function renderSummaryTable() {
    const sBody = document.getElementById("summaryBody");
    const pagDiv = document.getElementById("summaryPagination");
    const pageInfo = document.getElementById("summaryPageInfo");
    const fmt = (n) => new Intl.NumberFormat("id-ID").format(n || 0);

    if (allSummaryData.length === 0) {
        sBody.innerHTML =
            '<tr><td colspan="3" class="text-center text-muted">No data</td></tr>';
        pagDiv.classList.add("d-none");
        return;
    }

    const start = (currentSummaryPage - 1) * itemsPerSummaryPage;
    const end = start + itemsPerSummaryPage;
    const paginatedData = allSummaryData.slice(start, end);

    sBody.innerHTML = paginatedData
        .map(
            (row) => `
        <tr>
            <td class="fw-bold bg-light" style="font-size: 8px;">${row.gt_type}</td>
            <td class="text-end">${fmt(row.oe_stk_akhir)}</td>
            <td class="text-end">${fmt(row.ok_stk_akhir)}</td>
        </tr>`,
        )
        .join("");

    const totalPages = Math.ceil(allSummaryData.length / itemsPerSummaryPage);
    if (totalPages > 1) {
        pagDiv.classList.remove("d-none");
        pageInfo.innerText = `${currentSummaryPage} / ${totalPages}`;
    } else {
        pagDiv.classList.add("d-none");
    }
    if (typeof lucide !== "undefined") lucide.createIcons();
}

function nextSummaryPage() {
    const totalPages = Math.ceil(allSummaryData.length / itemsPerSummaryPage);
    if (currentSummaryPage < totalPages) {
        currentSummaryPage++;
        renderSummaryTable();
    }
}

function prevSummaryPage() {
    if (currentSummaryPage > 1) {
        currentSummaryPage--;
        renderSummaryTable();
    }
}

/**
 * INITIAL LOAD
 */
document.addEventListener("DOMContentLoaded", () => {
    if (typeof lucide !== "undefined") lucide.createIcons();
    refreshCalendar();
});
