function switchFisikContent(menu) {
    const container = document.getElementById("fisik-dynamic-content");

    // Animasi Loading
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-orange" role="status"></div>
            <p class="mt-2 fw-bold text-muted">Loading ${menu.replace("_", " ").toUpperCase()}...</p>
        </div>
    `;

    fetch(`/oracle-fisik/switch-menu?menu=${menu}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    })
        .then((response) => response.text())
        .then((html) => {
            container.innerHTML = html;

            // Inisialisasi ulang icon Lucide setelah konten baru dimuat
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }

            // Jalankan Script spesifik menu jika ada
            loadMenuScript(menu);
        })
        .catch((err) => {
            container.innerHTML = `<div class="alert alert-danger">Gagal memuat data menu ${menu}</div>`;
        });
}

function loadMenuScript(menu) {
    // Logika untuk memanggil file JS spesifik agar tidak bentrok
    console.log(`Script for ${menu} ready to initialize.`);
}
