/**
 * Script APPKSO - Client Side Logic (Premium Search)
 */

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const tableBody = document.getElementById("tableBody");
    const rows = tableBody.getElementsByTagName("tr");

    // FUNGSI SEARCH PREMIUM
    if (searchInput) {
        searchInput.addEventListener("keyup", function (e) {
            const searchText = e.target.value.toLowerCase();

            Array.from(rows).forEach((row) => {
                // Logic: Ambil semua teks dalam satu baris, gabungkan, lalu cek kata kunci
                // Ini jauh lebih aman daripada mengandalkan index kolom
                const rowText = row.textContent.toLowerCase();

                if (rowText.includes(searchText)) {
                    row.style.display = ""; // Tampilkan
                } else {
                    row.style.display = "none"; // Sembunyikan
                }
            });
        });
    }

    // Init Icons
    if (window.lucide) {
        lucide.createIcons();
    }
});
