document.addEventListener("DOMContentLoaded", function () {
    // 1. Live Clock & Date
    // 1. Live Clock & Date
    function updateClock() {
        const now = new Date();

        // Format jam manual dengan pemisah :
        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const seconds = String(now.getSeconds()).padStart(2, "0");
        const timeStr = `${hours}:${minutes}:${seconds}`;

        const liveClockElement = document.getElementById("liveClock");
        if (liveClockElement) {
            liveClockElement.textContent = timeStr;
        }

        // Format tanggal tetap pakai locale Indonesia
        const options = {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        };
        const dateStr = now.toLocaleDateString("id-ID", options);

        const liveDateElement = document.getElementById("liveDate");
        if (liveDateElement) {
            liveDateElement.textContent = dateStr;
        }
    }

    setInterval(updateClock, 1000);
    updateClock();

    // 2. Seamless Auto-Refresh (AJAX DOM Replacement)
    async function silentRefresh() {
        // KUNCI: JANGAN REFRESH KALAU MODAL LAGI KEBUKA
        if (document.body.classList.contains("modal-open")) {
            return;
        }

        try {
            let response = await fetch(window.location.href, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            if (!response.ok) throw new Error("Gagal mengambil data refresh");

            let htmlText = await response.text();
            let parser = new DOMParser();
            let doc = parser.parseFromString(htmlText, "text/html");

            let newCards = doc.getElementById("auditor-cards-container");
            if (newCards) {
                document.getElementById("auditor-cards-container").innerHTML =
                    newCards.innerHTML;
            }

            let newRightPanel = doc.getElementById("right-panel-container");
            if (newRightPanel) {
                document.getElementById("right-panel-container").innerHTML =
                    newRightPanel.innerHTML;
            }

            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }
        } catch (error) {
            console.error("Silent refresh error: ", error);
        }
    }

    // Auto-Refresh tiap 15 detik
    setInterval(silentRefresh, 10000);
});

// =========================================================================
// 3. FUNGSI UNTUK MEMBUKA MODAL DETAIL AUDITOR
// =========================================================================
function openDetailModal(auditorName, gedung) {
    const modalElement = document.getElementById("modalDetailAuditor");
    const modalTitle = document.getElementById("modalAuditorName");
    const tbody = document.getElementById("detailAuditorTbody");
    const summaryContainer = document.getElementById("modalSummaryContainer");
    const filterPic = document.getElementById("modalFilterPic");
    const filterStatus = document.getElementById("modalFilterStatus");

    // Reset Filters & State
    filterPic.innerHTML = '<option value="All">-- SEMUA PIC STOCK --</option>';
    filterStatus.value = "All";
    modalTitle.textContent = auditorName;
    summaryContainer.innerHTML =
        '<span class="text-white-50" style="font-size: 10px;">Loading data...</span>';

    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5">
                <i data-lucide="loader-2" class="lucide-spin text-info mb-2" style="width: 24px; height: 24px;"></i>
                <p class="text-white-50 mb-0">Sedang menarik data dari database...</p>
            </td>
        </tr>
    `;

    if (typeof lucide !== "undefined") lucide.createIcons();

    const bsModal = new bootstrap.Modal(modalElement);
    bsModal.show();

    document.body.classList.add("modal-open");
    modalElement.addEventListener(
        "hidden.bs.modal",
        function () {
            document.body.classList.remove("modal-open");
        },
        { once: true },
    );

    let fetchUrl = `/progress/detail?auditor=${encodeURIComponent(auditorName)}`;
    if (gedung) {
        fetchUrl += `&gedung=${encodeURIComponent(gedung)}`;
    }

    fetch(fetchUrl, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    })
        .then((response) => response.json())
        .then((data) => {
            // Suntik Table Utama
            tbody.innerHTML = data.html;

            // Suntik Matrix Summary
            if (data.summary_html) {
                summaryContainer.innerHTML = data.summary_html;
            }

            // Isi Dropdown Filter PIC Stock
            if (data.pic_list && data.pic_list.length > 0) {
                data.pic_list.forEach((pic) => {
                    if (pic !== "-") {
                        filterPic.innerHTML += `<option value="${pic}">${pic}</option>`;
                    }
                });
            }

            if (typeof lucide !== "undefined") lucide.createIcons();
        })
        .catch((error) => {
            console.error("Error fetching detail:", error);
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Gagal memuat data. Periksa koneksi atau console.</td></tr>`;
            summaryContainer.innerHTML = `<span class="text-danger" style="font-size: 10px;">Gagal memuat matrix summary.</span>`;
        });
}

// FUNGSI UNTUK FILTER TABEL MODAL MENGGUNAKAN VANILLA JS
function filterModalTable() {
    const selectedStatus = document.getElementById("modalFilterStatus").value;
    const selectedPic = document.getElementById("modalFilterPic").value;
    const rows = document.querySelectorAll("#detailAuditorTbody tr.detail-row");

    rows.forEach((row) => {
        const rowStatus = row.getAttribute("data-status");
        const rowPic = row.getAttribute("data-pic");

        // Logic AND (Harus cocok keduanya jika difilter)
        const matchStatus =
            selectedStatus === "All" || rowStatus === selectedStatus;
        const matchPic = selectedPic === "All" || rowPic === selectedPic;

        if (matchStatus && matchPic) {
            row.style.display = ""; // Munculkan
        } else {
            row.style.display = "none"; // Sembunyikan
        }
    });
}
