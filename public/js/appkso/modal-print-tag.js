document.addEventListener("DOMContentLoaded", function () {
    const picNoksoMap = window.picNoksoMap || {};

    const modalPicName = document.getElementById("modalPicName");
    const modalDocFrom = document.getElementById("modalDocFrom");
    const modalDocTo = document.getElementById("modalDocTo");

    const btnPrintNow = document.getElementById("btnPrintNow");

    // 1. Update DOC dropdown
    modalPicName.addEventListener("change", function () {
        const selectedPIC = this.value;

        modalDocFrom.innerHTML =
            '<option value="">-- Pilih Doc Awal --</option>';
        modalDocTo.innerHTML =
            '<option value="">-- Pilih Doc Akhir --</option>';

        if (!selectedPIC || !picNoksoMap[selectedPIC]) return;

        const list = picNoksoMap[selectedPIC].sort((a, b) =>
            a.localeCompare(b, undefined, { numeric: true }),
        );

        list.forEach((nokso) => {
            modalDocFrom.innerHTML += `<option value="${nokso}">${nokso}</option>`;
            modalDocTo.innerHTML += `<option value="${nokso}">${nokso}</option>`;
        });
    });

    // 2. DOC FROM change → filter DOC TO
    modalDocFrom.addEventListener("change", function () {
        const from = this.value;
        const pic = modalPicName.value;

        modalDocTo.innerHTML =
            '<option value="">-- Pilih Doc Akhir --</option>';

        if (!pic || !from) return;

        const list = picNoksoMap[pic];
        const startIndex = list.indexOf(from);
        const filtered = list.slice(startIndex);

        filtered.forEach((nokso) => {
            modalDocTo.innerHTML += `<option value="${nokso}">${nokso}</option>`;
        });
    });

    // 3. Ambil data form
    function getFormData() {
        const pic = modalPicName.value;
        const from = modalDocFrom.value;
        const to = modalDocTo.value;
        const tanggal = document.getElementById("modalTanggalSo").value; // tambah ini

        if (!pic || !from || !to || !tanggal) {
            alert("Isi semua filter termasuk tanggal!");
            return null;
        }

        return {
            pic_name: pic,
            doc_from: from,
            doc_to: to,
            tanggal_so: tanggal, // tambah ini
        };
    }

    // 4. Handle Print
    btnPrintNow.addEventListener("click", function () {
        const data = getFormData();
        if (!data) return;

        fetch(window.printPreviewRoute, {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(data),
        })
            .then(res => res.json())
            .then(res => {
                if (!res.success) return alert(res.message);

                // 🔥 OPEN BLANK TAB DENGAN CONTEXT URL (INI FIX UTAMA)
                const w = window.open("about:blank", "_blank");

                if (!w) {
                    alert("Popup diblok browser");
                    return;
                }

                // 🔥 paksa base URL supaya asset() jalan
                const base = window.location.origin;

                const html = res.html.replace(
                    /href="\/|src="\//g,
                    match => match.replace('"', `"${base}`)
                );

                w.document.open();
                w.document.write(html);
                w.document.close();

                setTimeout(() => {
                    w.focus();
                }, 300);
            })
            .catch(err => {
                console.error(err);
                alert("Gagal load print");
            });
    });
});
