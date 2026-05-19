/**
 * Script Khusus Modul Oracle Vs Aktual Fisik
 */

// State Management khusus Fisik
let currentMonthFisik = new Date().getMonth();
let currentYearFisik = new Date().getFullYear();
let selectedDateFisik = null;
let activeFisikChart = null;

/**
 * Fungsi inisialisasi yang dipanggil oleh dashboard.blade.php
 */
window.initFisikModul = function () {
    // --- KUNCI BIAR ICON MUNCUL ---
    if (window.lucide) {
        window.lucide.createIcons();
    }
    // ------------------------------

    renderCalendarFisik();
    if (typeof applyFiltersFisik === "function") {
        applyFiltersFisik();
    } else {
    }
};

/**
 * Contoh fungsi ambil data via AJAX
 */
function loadFisikDashboardData() {
    // Lu bisa pake sistem filter yang mirip sama module Barcode
}

/**
 * Render Kalender khusus modul Fisik
 */
function renderCalendarFisik() {
    const grid = document.getElementById("calendarGridFisik");
    if (!grid) return;

    // Logic kalender fisik lu masukin sini bro...
    // (Bisa copas logic dari barcode.js tapi ganti ID element-nya)
}

/**
 * Handle perubahan bulan di modul fisik
 */
window.changeMonthFisik = function (step) {
    currentMonthFisik += step;
    if (currentMonthFisik > 11) {
        currentMonthFisik = 0;
        currentYearFisik++;
    } else if (currentMonthFisik < 0) {
        currentMonthFisik = 11;
        currentYearFisik--;
    }
    renderCalendarFisik();
};

// Resize chart saat layar berubah
window.addEventListener("resize", function () {
    if (activeFisikChart) activeFisikChart.resize();
});

initFisikModul();

function switchFisikContent(menu) {
    const container = document.getElementById("fisik-dynamic-content");

    // Tampilkan Loading
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-orange" role="status"></div>
            <p class="mt-2 fw-bold text-muted">Sedang memuat halaman...</p>
        </div>
    `;

    // Ambil data lewat AJAX
    // Lu perlu buat 1 route di web.php yang handle parameter menu ini
    fetch(`/oracle-fisik/switch-menu?menu=${menu}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    })
        .then((response) => response.text())
        .then((html) => {
            container.innerHTML = html;
            if (window.lucide) lucide.createIcons(); // Refresh icon lucide
        })
        .catch((err) => {
            container.innerHTML =
                '<div class="alert alert-danger">Gagal load data bro!</div>';
        });
}
