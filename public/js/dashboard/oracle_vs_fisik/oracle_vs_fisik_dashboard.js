/**
 * Oracle Vs Fisik Dashboard Engine
 */

window.loadFisikDashboardData = function (warehouse) {
    if (!warehouse) return;

    // Sembunyikan placeholder awal, biarkan area metrics & chart tetap siap memproses data
    document.getElementById("gudangPlaceholder").classList.add("d-none");

    // Reset isi table & loading awal
    document.getElementById("tableContainerFisik").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Menarik Data...</div></div>';

    // Reset nilai UI menjadi "0" saat transisi data baru
    [
        "sum-unscanned",
        "sum-minus",
        "sum-plus",
        "sum-variance",
        "sum-gross",
        "oe-variance-pcs",
        "oe-sku-minus",
        "oe-sku-plus",
        "ok-variance-pcs",
        "ok-sku-minus",
        "ok-sku-plus",
        "mix-variance-pcs",
        "mix-sku-minus",
        "mix-sku-plus",
        "sum-ppm",
        "sum-variance-rate-2",
        "sum-variance-rate-3",
        "sum-price-variance",
        "price-variance-oe",
        "price-variance-ok",
        "sku-percentage",
        "sku-counted",
        "sku-onhand",
    ].forEach((id) => {
        if (document.getElementById(id))
            document.getElementById(id).innerText = "0";
    });

    // Tarik data dari Server
    $.get(`/oracle-fisik/get-comparison?warehouse=${warehouse}`)
        .done(function (res) {
            window.globalComparisonData = res.data;
            // CEK DATA DULU: JIKA KOSONG ATAU TIDAK ADA DATA
            if (!res.data || res.data.length === 0) {
                document
                    .getElementById("metricCardsArea")
                    .classList.remove("show");
                document
                    .getElementById("mainChartsArea")
                    .classList.add("d-none");

                // Tampilkan pesan kosong pada placeholder
                let placeholder = document.getElementById("gudangPlaceholder");
                placeholder.classList.remove("d-none");
                placeholder.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center p-5 text-center">
                        <i data-lucide="database-zap" style="width: 64px; height: 64px; color: #cbd5e1; margin-bottom: 15px;"></i>
                        <h4 class="text-muted fw-bold">Tidak Ada Data Ditemukan</h4>
                        <p class="text-muted">Gudang <strong class="text-primary">${warehouse}</strong> belum memiliki data untuk ditampilkan.</p>
                    </div>`;

                document.getElementById("tableContainerFisik").innerHTML = "";

                if (typeof lucide !== "undefined") lucide.createIcons();
                return; // Berhenti di sini
            }

            // JIKA DATA ADA: AMANKAN OPACITY & DISPLAY CONTAINER
            // JIKA DATA ADA: AMANKAN OPACITY & DISPLAY CONTAINER
            document.getElementById("gudangPlaceholder").innerHTML = "";
            document
                .getElementById("gudangPlaceholder")
                .classList.add("d-none");
            let metricsArea = document.getElementById("metricCardsArea");
            metricsArea.classList.add("show");
            metricsArea.style.opacity = "1"; // Paksa opacity jadi 1
            metricsArea.style.pointerEvents = "auto"; // Aktifkan klik

            let chartsArea = document.getElementById("mainChartsArea");
            chartsArea.classList.remove("d-none");
            chartsArea.style.opacity = "1"; // Paksa opacity area chart jika ada

            if (!res.success) {
                alert("Gagal menarik data: " + res.message);
                return;
            }

            // =========================================================================
            // UPDATE GLOBAL SUMMARY CARDS
            // =========================================================================
            if ($("#sum-unscanned").length)
                $("#sum-unscanned").text(
                    (res.summary.unscanned_sku || 0).toLocaleString("id-ID"),
                );

            if ($("#sum-minus").length)
                $("#sum-minus").text(
                    (res.summary.total_minus || 0).toLocaleString("id-ID"),
                );

            if ($("#sum-plus").length)
                $("#sum-plus").text(
                    (res.summary.total_plus || 0).toLocaleString("id-ID"),
                );

            if ($("#sum-variance").length)
                $("#sum-variance").text(
                    (res.summary.net_variance || 0).toLocaleString("id-ID"),
                );

            if ($("#sum-gross").length)
                $("#sum-gross").text(
                    (res.summary.gross_variance || 0).toLocaleString("id-ID"),
                );

            // =========================================================================
            // UPDATE VARIANCE GRADE OE
            // =========================================================================
            if (
                res.summary.oe_variance_pcs !== undefined ||
                res.summary.oe_sku_minus !== undefined
            ) {
                if ($("#oe-variance-pcs").length)
                    $("#oe-variance-pcs").text(
                        (res.summary.oe_variance_pcs || 0).toLocaleString(
                            "id-ID",
                        ),
                    );
                if ($("#oe-sku-minus").length)
                    $("#oe-sku-minus").text(
                        (res.summary.oe_sku_minus || 0).toLocaleString("id-ID"),
                    );
                if ($("#oe-sku-plus").length)
                    $("#oe-sku-plus").text(
                        (res.summary.oe_sku_plus || 0).toLocaleString("id-ID"),
                    );
            }

            // =========================================================================
            // UPDATE VARIANCE GRADE OK
            // =========================================================================
            if (
                res.summary.ok_variance_pcs !== undefined ||
                res.summary.ok_sku_minus !== undefined
            ) {
                if ($("#ok-variance-pcs").length)
                    $("#ok-variance-pcs").text(
                        (res.summary.ok_variance_pcs || 0).toLocaleString(
                            "id-ID",
                        ),
                    );
                if ($("#ok-sku-minus").length)
                    $("#ok-sku-minus").text(
                        (res.summary.ok_sku_minus || 0).toLocaleString("id-ID"),
                    );
                if ($("#ok-sku-plus").length)
                    $("#ok-sku-plus").text(
                        (res.summary.ok_sku_plus || 0).toLocaleString("id-ID"),
                    );
            }

            // --- FUNGSI HELPER UNTUK MENGUBAH ANGKA JADI FORMAT K ---
            const formatCompact = (num) => {
                if (num === null || num === undefined) return "0";
                let absNum = Math.abs(num);
                let sign = num < 0 ? "-" : "";

                // Miliar (Billion)
                if (absNum >= 1000000000) {
                    return (
                        sign +
                        (absNum / 1000000000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "B"
                    );
                }
                // Juta (Million)
                if (absNum >= 1000000) {
                    return (
                        sign +
                        (absNum / 1000000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "M"
                    );
                }
                // Ribu (Kilo)
                if (absNum >= 1000) {
                    return (
                        sign +
                        (absNum / 1000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "K"
                    );
                }

                // Kalau di bawah 1000, biarkan angka normal
                return num.toLocaleString("id-ID");
            };

            // UPDATE NILAI PRICE VARIANCE MENGGUNAKAN FORMAT K
            if ($("#sum-price-variance").length) {
                let val = res.summary.total_price_variance || 0;
                $("#sum-price-variance").text("Rp " + formatCompact(val));
            }

            if ($("#price-variance-oe").length) {
                let val = res.summary.oe_price_variance || 0;
                $("#price-variance-oe").text("Rp " + formatCompact(val));
            }

            if ($("#price-variance-ok").length) {
                let val = res.summary.ok_price_variance || 0;
                $("#price-variance-ok").text("Rp " + formatCompact(val));
            }

            // SUNTIKKAN PERSENTASE SKU KE CARD 6
            if ($("#sku-percentage").length) {
                let skuPct = res.summary.sku_percentage || 0;
                $("#sku-percentage").text(skuPct);
            }
            if ($("#sku-counted").length) {
                let counted = res.summary.total_item_appkso || 0;
                $("#sku-counted").text(counted.toLocaleString("id-ID"));
            }
            if ($("#sku-onhand").length) {
                let onhand = res.summary.total_item_oracle || 0;
                $("#sku-onhand").text(onhand.toLocaleString("id-ID"));
            }

            // =========================================================================
            // HITUNG & UPDATE CARD GABUNGAN (GRADE OE + GRADE OK)
            // =========================================================================
            let oeVariance = res.summary.oe_variance_pcs ?? 0;
            let oeMinus = res.summary.oe_sku_minus ?? 0;
            let oePlus = res.summary.oe_sku_plus ?? 0;

            let okVariance = res.summary.ok_variance_pcs ?? 0;
            let okMinus = res.summary.ok_sku_minus ?? 0;
            let okPlus = res.summary.ok_sku_plus ?? 0;

            if ($("#mix-variance-pcs").length)
                $("#mix-variance-pcs").text(
                    (oeVariance + okVariance).toLocaleString("id-ID"),
                );
            if ($("#mix-sku-minus").length)
                $("#mix-sku-minus").text(
                    (oeMinus + okMinus).toLocaleString("id-ID"),
                );
            if ($("#mix-sku-plus").length)
                $("#mix-sku-plus").text(
                    (oePlus + okPlus).toLocaleString("id-ID"),
                );

            if ($("#sum-ppm").length)
                $("#sum-ppm").text(
                    (res.summary.variance_ppm ?? 0).toLocaleString("id-ID"),
                );

            // =========================================================================
            // RE-RENDER ICONS & CHARTS
            // =========================================================================
            if (typeof lucide !== "undefined") {
                lucide.createIcons();
            }

            // Panggil fungsi render bawaan script kamu
            if (typeof window.renderSpeedometer === "function")
                window.renderSpeedometer(res.summary.accuracy_rate || 0);

            if (typeof window.renderTotalQtyChart === "function")
                window.renderTotalQtyChart(
                    res.summary.total_oracle || 0,
                    res.summary.total_appkso || 0,
                );

            if (typeof window.renderVarianceChart === "function")
                window.renderVarianceChart(res.data);

            if (typeof window.renderComparisonTable === "function")
                window.renderComparisonTable(res.data);

            // Ganti resize error global tadi dengan pengecekan aman di sini setelah chart ter-render
            setTimeout(function () {
                if (typeof mySpeedometerChart !== "undefined")
                    mySpeedometerChart.resize();
                if (typeof myTotalQtyChart !== "undefined")
                    myTotalQtyChart.resize();
                if (typeof myVarianceChart !== "undefined")
                    myVarianceChart.resize();
            }, 300);
        })
        .fail(function (xhr) {
            document
                .getElementById("gudangPlaceholder")
                .classList.remove("d-none");
            document.getElementById("metricCardsArea").classList.remove("show");
            document.getElementById("mainChartsArea").classList.add("d-none");

            document.getElementById("gudangPlaceholder").innerHTML = `
                <div class="alert alert-danger d-flex align-items-center py-2 px-4 border-0 rounded-3 shadow-sm">
                    <i data-lucide="alert-triangle" class="me-2" style="width: 18px; height: 18px;"></i>
                    <div class="fw-medium">Terjadi kesalahan pada server saat menarik data.</div>
                </div>`;
            document.getElementById("tableContainerFisik").innerHTML = "";
            if (typeof lucide !== "undefined") lucide.createIcons();
        });
};

// --- FUNGSI GLOBAL BUKA MODAL BIAR BISA DIPAKE TABEL & CHART ---
// Timpa fungsi openModalDetail lu dengan ini
window.openModalDetail = function (pattern, grade) {
    let warehouse = document.getElementById("filter-dashboard-wh").value;
    if (!warehouse) {
        alert("Pilih gudang terlebih dahulu.");
        return;
    }

    // Tulis judul awal selagi loading
    let titleEl = document.getElementById("modalTitlePattern");
    titleEl.innerText = `PATTERN: ${grade} ${pattern} (${warehouse}) | Memuat...`;

    let myModal = new bootstrap.Modal(
        document.getElementById("modalDetailPattern"),
    );
    myModal.show();

    document.getElementById("modalDetailContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Mencari anomali SKU dan Operator...</div></div>';

    $.get(
        `/oracle-fisik/get-detail-pattern?pattern=${encodeURIComponent(pattern)}&grade=${encodeURIComponent(grade)}&warehouse=${warehouse}`,
    )
        .done(function (res) {
            document.getElementById("modalDetailContent").innerHTML = res.html;

            // 1. GANTI TEXT DI HEADER TABEL
            $("#modalDetailContent th").each(function () {
                let text = $(this).text().trim().toUpperCase();
                if (text === "BC") $(this).text("COUNTED");
                if (text === "ORA") $(this).text("SNAPSHOT");
                if (text === "VAR") $(this).text("VARIANCE");
            });

            // =========================================================================
            // FIX FINAL LAPORAN 1: LAYOUT KIRI-KANAN (JUDUL KIRI - PCS KANAN)
            // =========================================================================

            // --- TABEL MINUS ---
            let totalMinusPcs = 0;
            $("#tableMinusExport tbody tr").each(function () {
                let varText = $(this)
                    .find("td:last")
                    .text()
                    .replace(/\./g, "")
                    .trim();
                let varVal = parseInt(varText) || 0;
                totalMinusPcs += varVal;
            });

            $("#modalDetailContent :contains('DATA MINUS (-)')")
                .filter(function () {
                    return $(this).children().length === 0;
                })
                .each(function () {
                    let originalTitle = $(this).text();
                    let formattedPcs =
                        Math.abs(totalMinusPcs).toLocaleString("id-ID");

                    // Ambil elemen induknya (biasanya .card-header atau div pembungkus)
                    let parentEl = $(this).parent();

                    // Paksa induknya menjadi Flexbox dengan justify-content-between
                    parentEl.addClass(
                        "d-flex justify-content-between align-items-center w-100",
                    );

                    // Ubah struktur teks judul di kiri, dan badge PCS di paling kanan
                    $(this).html(
                        `<span class="text-dark fw-semibold">${originalTitle}</span>`,
                    );

                    // Jika belum ada badge PCS di kanan induk, kita tambahkan (append)
                    if (parentEl.find(".pcs-badge-minus").length === 0) {
                        parentEl.append(`
                        <span class="pcs-badge-minus fw-bold px-2 py-1 rounded bg-danger-subtle text-danger"
                              style="font-size: 14px; min-width: 100px; text-align: right;">TOTAL VARIANCE :
                            ${formattedPcs} PCS
                        </span>
                    `);
                    }
                });

            // --- TABEL PLUS ---
            let totalPlusPcs = 0;
            $("#tablePlusExport tbody tr").each(function () {
                let varText = $(this)
                    .find("td:last")
                    .text()
                    .replace(/\./g, "")
                    .trim();
                let varVal = parseInt(varText) || 0;
                totalPlusPcs += varVal;
            });

            $("#modalDetailContent :contains('DATA PLUS (+)')")
                .filter(function () {
                    return $(this).children().length === 0;
                })
                .each(function () {
                    let originalTitle = $(this).text();
                    let formattedPcs =
                        Math.abs(totalPlusPcs).toLocaleString("id-ID");

                    // Ambil elemen induknya
                    let parentEl = $(this).parent();

                    // Paksa induknya menjadi Flexbox dengan justify-content-between
                    parentEl.addClass(
                        "d-flex justify-content-between align-items-center w-100",
                    );

                    // Ubah struktur teks judul di kiri, dan badge PCS di paling kanan
                    $(this).html(
                        `<span class="text-dark fw-semibold">${originalTitle}</span>`,
                    );

                    // Jika belum ada badge PCS di kanan induk, kita tambahkan (append)
                    if (parentEl.find(".pcs-badge-plus").length === 0) {
                        parentEl.append(`
                        <span class="pcs-badge-plus fw-bold px-2 py-1 rounded bg-primary-subtle text-primary"
                              style="font-size: 14px; min-width: 100px; text-align: right;"> TOTAL VARIANCE :
                            ${formattedPcs} PCS
                        </span>
                    `);
                    }
                });

            // =========================================================================
            // BAGIAN HEADER UTAMA MODAL (HITUNG TOTAL SKU DINAMIS: MINUS + PLUS)
            // =========================================================================
            let totalGlobalVariance = totalPlusPcs + totalMinusPcs;
            let formattedGlobalPcs =
                totalGlobalVariance.toLocaleString("id-ID");

            // PERBAIKAN: Hitung total SKU secara dinamis dari hasil penjumlahan minus + plus
            let skuMinusObj = res.summary.minus_sku || 0;
            let skuPlusObj = res.summary.plus_sku || 0;
            let totalSkuDinamis = skuMinusObj + skuPlusObj;

            // Cetak ke HTML menggunakan totalSkuDinamis
            titleEl.innerHTML = `PATTERN: <span class="text-warning">${grade} ${pattern}</span> (${warehouse}) &nbsp;|&nbsp; ${formattedGlobalPcs} Pcs dari ${totalSkuDinamis} SKU &nbsp;|&nbsp; <span class="text-danger">SKU Minus (-) ${skuMinusObj}</span> &nbsp;&amp;&nbsp; <span class="text-primary">SKU Plus (+) ${skuPlusObj}</span>`;
        })
        .fail(function (xhr) {
            document.getElementById("modalDetailContent").innerHTML =
                '<div class="p-4 text-danger text-center">Gagal memuat detail.</div>';
            titleEl.innerText = `PATTERN: ${grade} ${pattern} (${warehouse}) | ERROR`;
        });
};

// grafik progress
window.renderSpeedometer = function (accuracyRate) {
    let chartDom = document.getElementById("speedometerChart");
    if (!chartDom) return;
    let chart = echarts.getInstanceByDom(chartDom) || echarts.init(chartDom);

    chart.setOption(
        {
            tooltip: { formatter: "{a} <br/>{b} : {c}%" },
            grid: {
                top: 0,
                bottom: 0,
                left: 0,
                right: 0,
                containLabel: false,
            },
            series: [
                {
                    name: "Accuracy",
                    type: "gauge",
                    // 2. GEDEIN RADIUS (Default ECharts biasanya cuma 75% atau 80%)
                    radius: "105%",
                    // 3. TURUNIN POSISI TENGAHNYA (X, Y) biar gak kepotong atasnya
                    center: ["50%", "55%"],
                    progress: { show: true, width: 10 },
                    axisLine: { lineStyle: { width: 10 } },
                    axisTick: { show: false },
                    splitLine: {
                        length: 12,
                        lineStyle: { width: 2, color: "#999" },
                    },
                    pointer: { length: "70%", width: 3 },
                    data: [{ value: accuracyRate, name: "Progress" }],
                    detail: {
                        valueAnimation: true,
                        formatter: "{value}%",
                        fontSize: 18,
                        offsetCenter: [0, "60%"],
                    },
                    title: { offsetCenter: [0, "15%"], fontSize: 12 },
                },
            ],
        },
        true,
    );
};

// grafik total qty
window.renderTotalQtyChart = function (totalOracle, totalAppkso) {
    let chartDom = document.getElementById("totalQtyChart");
    if (!chartDom) return;
    let chart = echarts.getInstanceByDom(chartDom) || echarts.init(chartDom);

    // Helper format ribuan
    const formatRibuan = (val) => {
        if (val === undefined || val === null) return 0;
        // Gunakan Math.abs agar tanda minus tidak double jika nanti di-format manual
        return Math.abs(val)
            .toString()
            .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    };

    // REQ BARU: Hitung selisih/variance
    const variance = totalAppkso - totalOracle;
    // Tentukan tanda plus/minus atau netral
    const sign = variance > 0 ? "+" : variance < 0 ? "-" : "";
    const varianceText = `${sign}${formatRibuan(variance)}`;
    // Tentukan warna teks variance (Merah jika minus, Hijau jika surplus/plus, Abu jika seimbang)
    const varianceColor =
        variance > 0 ? "#198754" : variance < 0 ? "#dc3545" : "#6c757d";

    chart.setOption(
        {
            // Mengubah title menjadi array untuk menampung Judul Utama DAN Teks di Tengah Lingkaran
            title: [
                {
                    // Title Utama (Tetap sama seperti sebelumnya)
                    text: "{icon| } Total Qty (Counted Vs On-hand)",
                    left: "center",
                    top: "0%", // Dipaksa di paling atas
                    textStyle: {
                        fontSize: 13,
                        color: "#495057",
                        rich: {
                            icon: {
                                backgroundColor: {
                                    image: 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%23495057" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H3v5M21 12v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5M21 12H3M3 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2"/></svg>',
                                },
                                height: 14,
                                width: 14,
                            },
                        },
                    },
                },
                {
                    // SUB-TITLE UNTUK VARIANCE DI TENGAH LINGKARAN
                    text: `{label|Variance}\n{val|${varianceText}}`,
                    left: "49.5%", // Sedikit disesuaikan agar center presisi
                    top: "48%", // Disesuaikan dengan center Pie yang bernilai 52%
                    textAlign: "center",
                    textVerticalAlign: "middle",
                    textStyle: {
                        rich: {
                            label: {
                                fontSize: 11,
                                color: "#6c757d",
                                fontWeight: "normal",
                                padding: [0, 0, 4, 0], // Kasih jarak bawah ke angka
                            },
                            val: {
                                fontSize: 15,
                                fontWeight: "bold",
                                color: varianceColor, // Warna dinamis sesuai hasil variance
                            },
                        },
                    },
                },
            ],
            tooltip: {
                trigger: "item",
                formatter: function (params) {
                    return `${params.name}: <b>${formatRibuan(params.value)}</b> (${params.percent}%)`;
                },
            },
            legend: {
                bottom: "2%",
                left: "center",
                itemWidth: 12,
                itemHeight: 12,
                textStyle: { fontSize: 11 },
            },
            series: [
                {
                    name: "Total Qty",
                    type: "pie",
                    radius: ["40%", "70%"],
                    center: ["50%", "52%"],
                    avoidLabelOverlap: false,
                    itemStyle: {
                        borderRadius: 6,
                        borderColor: "#fff",
                        borderWidth: 2,
                    },
                    label: {
                        show: true,
                        position: "outside",
                        formatter: function (params) {
                            return formatRibuan(params.value);
                        },
                        fontWeight: "bold",
                        fontSize: 11,
                    },
                    labelLine: {
                        show: true,
                        length: 6,
                        length2: 8,
                        smooth: true,
                    },
                    data: [
                        {
                            value: totalOracle,
                            name: "On-hand",
                            itemStyle: { color: "#495057" },
                        },
                        {
                            value: totalAppkso,
                            name: "Counted",
                            itemStyle: { color: "#0d6efd" },
                        },
                    ],
                },
            ],
        },
        true,
    );

    window.addEventListener("resize", () => chart.resize());
};

window.renderVarianceChart = function (data) {
    let chartDom = document.getElementById("varianceChart");
    if (!chartDom) return;
    let chart = echarts.getInstanceByDom(chartDom) || echarts.init(chartDom);

    if (!data || data.length === 0) {
        chart.clear();
        return;
    }

    let problematicItems = data.filter((d) => parseInt(d.variance) !== 0);
    problematicItems.sort(
        (a, b) => Math.abs(b.variance) - Math.abs(a.variance),
    );
    let top10 = problematicItems.slice(0, 10).reverse();

    let categories = top10.map((d) => `${d.pattern} | ${d.grade}`); // Pakai separator yang gampang di-split
    let values = top10.map((d) => parseInt(d.variance));

    let option = {
        title: {
            text: "Top 10 Pattern dengan Selisih Terbesar",
            textStyle: { fontSize: 13, color: "#495057" },
            left: "center",
        },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
            formatter: function (params) {
                let val = params[0].value;
                let status = val < 0 ? "Kurang (Minus)" : "Lebih (Plus)";
                return `${params[0].name}<br/>Variance: <b>${val.toLocaleString()}</b> pcs (${status})`;
            },
        },
        grid: {
            left: "3%",
            right: "8%",
            bottom: "3%",
            top: "15%",
            containLabel: true,
        },
        xAxis: {
            type: "value",
            splitLine: { lineStyle: { type: "dashed", color: "#e9ecef" } },
        },
        yAxis: {
            type: "category",
            data: categories,
            axisLabel: { fontSize: 10, fontWeight: "bold" },
        },
        series: [
            {
                name: "Variance",
                type: "bar",
                data: values.map((v) => ({
                    value: v,
                    itemStyle: {
                        color: v < 0 ? "#dc3545" : "#0d6efd",
                        borderRadius: 2,
                    },
                })),
                label: {
                    show: true,
                    position: "outside",
                    formatter: (p) => p.value.toLocaleString(),
                    fontSize: 10,
                    fontWeight: "bold",
                },
            },
        ],
    };

    chart.setOption(option, true);

    // BIKIN GRAFIK BISA DI KLIK
    chart.off("click"); // cegah double trigger
    chart.on("click", function (params) {
        // params.name = "PATTERN A | OE"
        let splitName = params.name.split(" | ");
        let pattern = splitName[0];
        let grade = splitName[1];
        window.openModalDetail(pattern, grade);
    });

    window.addEventListener("resize", () => chart.resize());
};

window.renderComparisonTable = function (data) {
    if (!data || data.length === 0) {
        document.getElementById("tableContainerFisik").innerHTML =
            '<div class="text-center text-muted p-4">Tidak ada data untuk gudang ini.</div>';
        return;
    }

    let html = '<div class="row g-3">';
    ["OE", "OK"].forEach((grade) => {
        let filtered = data.filter(
            (i) =>
                i.grade == grade &&
                (parseInt(i.qty_appkso) > 0 || parseInt(i.qty_oracle) > 0),
        );
        if (filtered.length === 0) return;

        html += `
        <div class="col-12 col-xl-6">
            <div class="table-responsive border rounded bg-white shadow-sm" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="sticky-top bg-dark text-white" style="font-size: 11px; z-index: 1;">
                        <tr>
                            <th class="ps-2 py-2">Pattern (${grade})</th>
                            <th class="text-end py-2">Counted</th>
                            <th class="text-end py-2">On-hand</th>
                            <th class="text-end py-2">Variance</th>
                            <th class="text-center py-2">SKU (-)</th>
                            <th class="text-center py-2">SKU (+)</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 12px;">
                        ${filtered
                            .map((row) => {
                                let varClass =
                                    row.variance < 0
                                        ? "text-danger"
                                        : row.variance > 0
                                          ? "text-primary"
                                          : "text-success";
                                return `
                            <tr class="clickable-row" data-pattern="${row.pattern}" data-grade="${row.grade}" style="cursor:pointer;">
                                <td class="text-start fw-bold text-dark ps-2">${row.pattern}</td>
                                <td class="text-end">${parseInt(row.qty_appkso).toLocaleString()}</td>
                                <td class="text-end">${parseInt(row.qty_oracle).toLocaleString()}</td>
                                <td class="text-end fw-bold ${varClass}">${parseInt(row.variance).toLocaleString()}</td>
                                <td class="text-center text-danger fw-bold">${row.sku_minus}</td>
                                <td class="text-center text-primary fw-bold">${row.sku_plus}</td>
                            </tr>
                            `;
                            })
                            .join("")}
                    </tbody>
                </table>
            </div>
        </div>`;
    });
    document.getElementById("tableContainerFisik").innerHTML = html + "</div>";
};

// Event Delegation buat Klik Tabel
$(document)
    .off("click", ".clickable-row")
    .on("click", ".clickable-row", function () {
        let pattern = $(this).data("pattern");
        let grade = $(this).data("grade");
        window.openModalDetail(pattern, grade);
    });

// --- EVENT LISTENER BUAT KLIK BARIS ITEM DI MODAL 1 DAN MODAL 5 ---
$(document)
    .off("click", ".clickable-item")
    .on("click", ".clickable-item", function () {
        let itemCode = $(this).data("item");
        let warehouse = document.getElementById("filter-dashboard-wh").value;

        document.getElementById("modalTitleScanHistory").innerHTML =
            `ITEM: <span class="text-warning">${itemCode}</span>`;

        // ==============================================================
        // TAMBAHAN CHEAT Z-INDEX BIAR MODAL NUMPUK DENGAN BENAR
        // ==============================================================
        let modalScanEl = document.getElementById("modalScanHistory");
        modalScanEl.style.zIndex = 1060; // Default Bootstrap cuma 1055

        let scanModal = new bootstrap.Modal(modalScanEl);
        scanModal.show();

        // Akali layar hitam (backdrop) biar ikut naik posisinya di atas Modal Fullscreen
        setTimeout(() => {
            let backdrops = document.querySelectorAll(".modal-backdrop");
            if (backdrops.length > 1) {
                // Backdrop yang terakhir kali muncul kita naikin posisinya
                backdrops[backdrops.length - 1].style.zIndex = 1059;
            }
        }, 105);
        // ==============================================================

        let tbody = document.getElementById("tbodyScanHistory");
        tbody.innerHTML =
            '<tr><td colspan="7" class="text-center p-4"><div class="spinner-border text-primary"></div></td></tr>';

        $.get(
            `/oracle-fisik/get-scan-history?item=${encodeURIComponent(itemCode)}&warehouse=${warehouse}`,
        )
            .done(function (res) {
                if (!res.success || res.data.length === 0) {
                    tbody.innerHTML =
                        '<tr><td colspan="7" class="text-center text-danger py-4 fw-bold">Belum ada data scan fisik untuk item ini.</td></tr>';
                    return;
                }

                let html = res.data
                    .map(
                        (row, idx) => `
                <tr>
                    <td class="text-center text-muted">${idx + 1}</td>
                    <td class="fw-bold">${row.opr || "-"}</td>
                    <td>${row.oprname || "-"}</td>
                    <td><span class="badge bg-secondary">${row.nokso || "-"}</span></td>
                    <td class="fw-bold">${row.item}</td>
                    <td class="text-truncate" style="max-width:200px;">${row.deskripsi || "-"}</td>
                    <td class="text-end text-primary fw-bold">${parseInt(row.qty).toLocaleString()}</td>
                </tr>
            `,
                    )
                    .join("");

                // Tambah baris total di bawah
                html += `
                <tr class="bg-light">
                    <td colspan="6" class="text-end fw-black">TOTAL Counted:</td>
                    <td class="text-end text-primary fw-black fs-6">${parseInt(res.total_qty).toLocaleString()}</td>
                </tr>
            `;
                tbody.innerHTML = html;
            })
            .fail(function () {
                tbody.innerHTML =
                    '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat riwayat scan.</td></tr>';
            });
    });

// --- FUNGSI PRINT MODAL ---
window.printModal = function () {
    const title = document.getElementById("modalTitlePattern").innerHTML;
    const content = document.getElementById("modalDetailContent").innerHTML;

    const printWindow = window.open("", "", "height=800,width=1200");

    if (!printWindow) {
        alert(
            "Gagal membuka jendela cetak. Mohon periksa apakah pop-up diblokir oleh browser.",
        );
        return;
    }

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Print Variance</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page {
                    size: A4 portrait;
                    margin: 10mm;
                }
                body {
                    font-size: 8px;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                /* INI KUNCI UTAMANYA: Buka paksa batasan max-height biar ngeprint full ke bawah */
                .table-responsive {
                    max-height: none !important;
                    overflow: visible !important;
                }
                /* Mastiin warna header ga hilang pas di-print */
                .bg-danger { background-color: #dc3545 !important; color: white !important;}
                .bg-primary { background-color: #0d6efd !important; color: white !important;}
                .bg-dark { background-color: #212529 !important; color: white !important;}

                /* Sembunyiin efek UI yg ga guna di kertas */
                .shadow, .shadow-sm { box-shadow: none !important; }
                .border-0 { border: 1px solid #dee2e6 !important; }
                table td, table th { border: 1px solid #dee2e6 !important; }

                /* Sembunyiin cursor pointer & scrollbar */
                .clickable-item { cursor: default !important; }
                ::-webkit-scrollbar { display: none; }
            </style>
        </head>
        <body class="p-4">
            <div class="mb-4 p-3 bg-dark text-white rounded">
                <h5 class="mb-0 fw-bold">${title}</h5>
            </div>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Tunggu 1 detik biar file CSS Bootstrap kelar didownload sama browser pop-up
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 1000);
};

// --- FUNGSI EXPORT EXCEL ---
// --- FUNGSI EXPORT EXCEL BARU (REAL .XLSX + BORDER) ---
window.exportModalExcel = function () {
    // 1. Ganti judul dan format file sesuai request lu
    let sheetName = "Data Plus & Minus";
    let fileName = "Data_Plus_Minus.xlsx";

    if (typeof TableToExcel !== "undefined") {
        try {
            console.log("Memproses Excel menggunakan excel.min.js bawaan...");

            let tempTable = document.createElement("table");
            tempTable.setAttribute("id", "tempExportTable");
            tempTable.style.display = "none";

            let tbody = document.createElement("tbody");

            // BARIS 1: JUDUL UTAMA (Bersih, Sesuai Request Lu)
            let trTitle = document.createElement("tr");
            trTitle.innerHTML = `<th colspan="13" style="font-size: 14px; font-weight: bold; text-align: left; background-color: #212529; color: #ffffff; border: 1px solid #000000;">Data Plus & Minus</th>`;
            tbody.appendChild(trTitle);

            // Jarak baris kosong pemisah
            let trSpacer = document.createElement("tr");
            trSpacer.innerHTML = `<td colspan="13" style="height: 15px; border: none;"></td>`;
            tbody.appendChild(trSpacer);

            // BARIS HEADER UTAMA KELOMPOK
            let trHeader = document.createElement("tr");
            trHeader.innerHTML = `
                <th colspan="6" style="background-color: #dc3545; color: #ffffff; text-align: center; font-weight: bold; border: 1px solid #000000;">DATA MINUS (-)</th>
                <th style="border: none; width: 30px;"></th> <th colspan="6" style="background-color: #0d6efd; color: #ffffff; text-align: center; font-weight: bold; border: 1px solid #000000;">DATA PLUS (+)</th>
            `;
            tbody.appendChild(trHeader);

            // BARIS SUB-HEADER KOLOM DENGAN BORDER
            let thStyle = `style="background-color: #f8f9fa; font-weight: bold; text-align: center; border: 1px solid #000000;"`;
            let trSubHeader = document.createElement("tr");
            trSubHeader.innerHTML = `
                <th ${thStyle}>NO</th><th ${thStyle}>ITEM CODE</th><th ${thStyle}>DESCRIPTION</th><th ${thStyle}>BC</th><th ${thStyle}>ORA</th><th ${thStyle}>VAR</th>
                <th style="border: none;"></th>
                <th ${thStyle}>NO</th><th ${thStyle}>ITEM CODE</th><th ${thStyle}>DESCRIPTION</th><th ${thStyle}>BC</th><th ${thStyle}>ORA</th><th ${thStyle}>VAR</th>
            `;
            tbody.appendChild(trSubHeader);

            // TARIK DATA DARI VIEW MODAL YANG AKTIF
            let minusRows = document.querySelectorAll(
                "#tableMinusExport tbody tr",
            );
            let plusRows = document.querySelectorAll(
                "#tablePlusExport tbody tr",
            );
            let maxRows = Math.max(minusRows.length, plusRows.length);

            for (let i = 0; i < maxRows; i++) {
                let tr = document.createElement("tr");

                // --- GENERATE SISI DATA MINUS (KIRI) ---
                if (
                    minusRows[i] &&
                    !minusRows[i].innerText.includes("Tidak ada data")
                ) {
                    let cells = minusRows[i].querySelectorAll("td");
                    cells.forEach((c) => {
                        let td = document.createElement("td");
                        td.innerText = c.innerText.trim();
                        // Jaga posisi teks (Angka kanan, teks kiri)
                        if (
                            c.classList.contains("text-end") ||
                            c.innerText.match(/^[0-9.-]+$/)
                        )
                            td.style.textAlign = "right";
                        if (c.classList.contains("text-center"))
                            td.style.textAlign = "center";
                        td.style.border = "1px solid #000000"; // Paksa Border Hitam di Sel
                        tr.appendChild(td);
                    });
                } else {
                    for (let j = 0; j < 6; j++) {
                        let td = document.createElement("td");
                        td.style.border = "1px solid #000000";
                        tr.appendChild(td);
                    }
                }

                // KOLOM TENGAH (KOSONG TANPA BORDER)
                let tdSpacer = document.createElement("td");
                tdSpacer.style.border = "none";
                tr.appendChild(tdSpacer);

                // --- GENERATE SISI DATA PLUS (KANAN) ---
                if (
                    plusRows[i] &&
                    !plusRows[i].innerText.includes("Tidak ada data")
                ) {
                    let cells = plusRows[i].querySelectorAll("td");
                    cells.forEach((c) => {
                        let td = document.createElement("td");
                        td.innerText = c.innerText.trim();
                        if (
                            c.classList.contains("text-end") ||
                            c.innerText.match(/^[0-9.-]+$/)
                        )
                            td.style.textAlign = "right";
                        if (c.classList.contains("text-center"))
                            td.style.textAlign = "center";
                        td.style.border = "1px solid #000000"; // Paksa Border Hitam di Sel
                        tr.appendChild(td);
                    });
                } else {
                    for (let j = 0; j < 6; j++) {
                        let td = document.createElement("td");
                        td.style.border = "1px solid #000000";
                        tr.appendChild(td);
                    }
                }

                tbody.appendChild(tr);
            }

            tempTable.appendChild(tbody);
            document.body.appendChild(tempTable);

            // DOWNLOAD LANGSUNG FORMAT .XLSX REAL VIA LIBRARY LU
            TableToExcel.convert(document.getElementById("tempExportTable"), {
                name: fileName,
                sheet: { name: sheetName },
            });

            document.body.removeChild(tempTable);
            return;
        } catch (e) {
            console.warn(
                "TableToExcel gagal diproses, beralih ke Fallback CSV:",
                e,
            );
            if (document.getElementById("tempExportTable")) {
                document.body.removeChild(
                    document.getElementById("tempExportTable"),
                );
            }
        }
    }

    // =========================================================================
    // FALLBACK CSV: Aktif otomatis hanya jika library excel.min.js lu rusak
    // =========================================================================
    let csv = [];
    csv.push("sep=;");
    csv.push([`"Data Plus & Minus"`]);
    csv.push([]);

    const extractTableToCSV = (tableId, headerText) => {
        let table = document.getElementById(tableId);
        if (!table) return;
        csv.push([`"${headerText}"`]);
        let rows = table.querySelectorAll("tr");
        for (let i = 0; i < rows.length; i++) {
            let rowData = [];
            let cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText
                    .replace(/(\r\n|\n|\r)/gm, " ")
                    .replace(/"/g, '""')
                    .trim();
                text = text.replace(/\./g, "");
                rowData.push(`"${text}"`);
            }
            csv.push(rowData.join(";"));
        }
        csv.push([]);
    };

    extractTableToCSV("tableMinusExport", "DATA MINUS (-)");
    extractTableToCSV("tablePlusExport", "DATA PLUS (+)");

    let bom = new Uint8Array([0xef, 0xbb, 0xbf]);
    let csvFile = new Blob([bom, csv.join("\n")], {
        type: "text/csv;charset=utf-8;",
    });
    let downloadLink = document.createElement("a");
    downloadLink.download = `Data_Plus_Minus.csv`;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
};

// --- FUNGSI BUKA MODAL UNSCANNED SKU (DARI CARD 6) ---
window.openModalUnscanned = function () {
    let warehouse = document.getElementById("filter-dashboard-wh").value;
    if (!warehouse) return;

    let myModal = new bootstrap.Modal(
        document.getElementById("modalUnscannedSKU"),
    );
    myModal.show();

    let tbodyOE = document.getElementById("tbody-unscanned-oe");
    let tbodyOK = document.getElementById("tbody-unscanned-ok");
    tbodyOE.innerHTML = tbodyOK.innerHTML =
        '<tr><td colspan="3" class="text-center p-3">Loading...</td></tr>';

    document.getElementById("searchUnscanned").value = "";

    $.get(`/oracle-fisik/get-unscanned-items?warehouse=${warehouse}`).done(
        function (res) {
            let dataOE = res.data.filter((i) => i.grade === "OE");
            let dataOK = res.data.filter((i) => i.grade === "OK");

            // Render Fungsi Helper
            const renderRows = (arr) =>
                arr
                    .map(
                        (row, idx) => `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="fw-bold">${row.item}</td>
            <td class="text-truncate" style="max-width: 120px;">${row.description || "-"}</td>
            <td class="text-end fw-bold ${row.qty_sisa > 0 ? "text-danger" : "text-dark"}">
                ${parseInt(row.qty_sisa).toLocaleString()}
            </td>
            <td class="text-center">
                <div class="progress position-relative" style="height: 18px; font-size: 10px; background-color: #e9ecef;">
                    <div class="progress-bar progress-bar-striped bg-warning"
                         style="width: ${row.persen}%; height: 100%;">
                    </div>
                    <span class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center fw-bold text-dark">
                        ${row.persen}%
                    </span>
                </div>
            </td>
        </tr>
    `,
                    )
                    .join("");

            tbodyOE.innerHTML = dataOE.length
                ? renderRows(dataOE)
                : '<tr><td colspan="4" class="text-center text-muted">Bersih!</td></tr>'; // colspan 4
            tbodyOK.innerHTML = dataOK.length
                ? renderRows(dataOK)
                : '<tr><td colspan="4" class="text-center text-muted">Bersih!</td></tr>'; // colspan 4

            document.getElementById("count-oe").innerText =
                dataOE.length + " Data";
            document.getElementById("count-ok").innerText =
                dataOK.length + " Data";
        },
    );
};

window.filterUnscannedTable = function () {
    let input = document.getElementById("searchUnscanned").value.toLowerCase();
    let tables = ["tbody-unscanned-oe", "tbody-unscanned-ok"];

    tables.forEach((tableId) => {
        let tbody = document.getElementById(tableId);
        let rows = tbody.getElementsByTagName("tr");

        for (let i = 0; i < rows.length; i++) {
            let row = rows[i];
            // Cek apakah baris ini bukan baris "Bersih!"
            if (row.innerText.toLowerCase().includes("bersih")) continue;

            // Ambil text dari kolom ITEM CODE (index 1) dan DESKRIPSI (index 2)
            let itemText = row.cells[1].innerText.toLowerCase();
            let descText = row.cells[2].innerText.toLowerCase();

            if (itemText.includes(input) || descText.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
};

// --- FUNGSI BUKA MODAL 1: REKAP PRICE VARIANCE ---
window.openModalPriceVariance = function () {
    let warehouse = document.getElementById("filter-dashboard-wh").value;
    if (!warehouse) return;

    // Pastikan data global sudah ada
    if (!window.globalComparisonData) {
        alert("Data belum siap, silakan refresh dashboard.");
        return;
    }

    let myModal = new bootstrap.Modal(
        document.getElementById("modalPriceVariance"),
    );
    myModal.show();

    let tbody = document.getElementById("tbody-price-variance-rekap");
    tbody.innerHTML = "";

    // Ambil data yang punya selisih (variance tidak sama dengan 0)
    let filteredData = window.globalComparisonData.filter(
        (i) => parseInt(i.variance) !== 0,
    );

    if (filteredData.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center p-4 text-muted">Tidak ada variance, data aman!</td></tr>';
        return;
    }

    // Render baris ke tabel
    let html = filteredData
        .map((row) => {
            let varClass = row.variance < 0 ? "text-danger" : "text-primary";
            return `
        <tr class="clickable-price-row" data-pattern="${row.pattern}" data-grade="${row.grade}" style="cursor:pointer; transition: background 0.2s;">
            <td class="text-start fw-bold text-dark ps-3">${row.pattern} (${row.grade})</td>
            <td class="text-end">${parseInt(row.qty_appkso).toLocaleString("id-ID")}</td>
            <td class="text-end">${parseInt(row.qty_oracle).toLocaleString("id-ID")}</td>
            <td class="text-end fw-bold ${varClass}">${parseInt(row.variance).toLocaleString("id-ID")}</td>
            <td class="text-center text-danger fw-bold">${row.sku_minus}</td>
            <td class="text-center text-primary fw-bold">${row.sku_plus}</td>
        </tr>`;
        })
        .join("");

    tbody.innerHTML = html;
};

// --- EVENT LISTENER KLIK BARIS DI MODAL 1 UNTUK BUKA MODAL 2 ---
$(document)
    .off("click", ".clickable-price-row")
    .on("click", ".clickable-price-row", function () {
        let pattern = $(this).data("pattern");
        let grade = $(this).data("grade");
        window.openModalDetailPrice(pattern, grade);
    });

// --- FUNGSI BUKA MODAL 2: DETAIL ITEM & HARGA ---
window.openModalDetailPrice = function (pattern, grade) {
    let warehouse = document.getElementById("filter-dashboard-wh").value;

    // Set judul modal
    let titleEl = document.getElementById("modalTitlePriceDetail");
    titleEl.innerHTML = `PATTERN: <span class="text-warning">${grade} ${pattern}</span> (${warehouse})`;

    // Tampilkan modal 2 (Otomatis numpuk di atas Modal 1)
    let detailModal = new bootstrap.Modal(
        document.getElementById("modalDetailPricePattern"),
    );
    detailModal.show();

    // Sembunyikan alert saat awal loading
    document.getElementById("alert-missing-price").classList.add("d-none");
    document.getElementById("modalPriceDetailContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-danger"></div><div class="mt-2 text-muted">Mengambil data harga dan kalkulasi variance...</div></div>';

    $.get(
        `/oracle-fisik/get-detail-price-pattern?pattern=${encodeURIComponent(pattern)}&grade=${encodeURIComponent(grade)}&warehouse=${warehouse}`,
    )
        .done(function (res) {
            // Tembak HTML dari blade partial ke dalam div
            document.getElementById("modalPriceDetailContent").innerHTML =
                res.html;

            // Re-render icon lucide kalau ada (buat icon warning harga 0)
            if (typeof lucide !== "undefined") lucide.createIcons();

            // LOGIC MENAMPILKAN ALERT JIKA ADA HARGA YANG BOLONG
            let alertBox = document.getElementById("alert-missing-price");
            let countSpan = document.getElementById("missing-price-count");

            if (res.summary.missing_price_count > 0) {
                countSpan.innerText = res.summary.missing_price_count;
                alertBox.classList.remove("d-none");
                alertBox.classList.add("d-flex");
            } else {
                alertBox.classList.add("d-none");
                alertBox.classList.remove("d-flex");
            }

            // UPDATE HEADER MODAL DENGAN SUMMARY TOTAL RUPIAH
            let totalGlobalPcs =
                res.summary.total_pcs_variance.toLocaleString("id-ID");
            let totalGlobalRp =
                res.summary.total_rp_variance.toLocaleString("id-ID");

            titleEl.innerHTML = `PATTERN: <span class="text-warning">${grade} ${pattern}</span> (${warehouse}) &nbsp;|&nbsp;
                ${totalGlobalPcs} Pcs (Rp ${totalGlobalRp}) dari ${res.summary.total_sku_dinamis} SKU &nbsp;|&nbsp;
                <span class="text-danger">SKU Minus (-) ${res.summary.sku_minus}</span> &nbsp;&amp;&nbsp;
                <span class="text-primary">SKU Plus (+) ${res.summary.sku_plus}</span>`;
        })
        .fail(function () {
            document.getElementById("modalPriceDetailContent").innerHTML =
                '<div class="p-4 text-danger text-center fw-bold"><i data-lucide="x-circle" class="me-2"></i>Gagal memuat detail harga.</div>';
            if (typeof lucide !== "undefined") lucide.createIcons();
            titleEl.innerText = `PATTERN: ${grade} ${pattern} (${warehouse}) | ERROR`;
        });
};

// --- FUNGSI BUKA MODAL DETAIL PER GRADE (DARI CARD 1, 2, 3) ---
window.openModalGrade = function (grade) {
    let warehouse = document.getElementById("filter-dashboard-wh").value;
    if (!warehouse) {
        alert("Pilih gudang terlebih dahulu.");
        return;
    }

    let titleEl = document.getElementById("modalTitleGrade");
    let gradeLabel = grade === "MIX" ? "GABUNGAN (OE + OK)" : grade;
    titleEl.innerHTML = `GRADE: <span class="text-warning">${gradeLabel}</span> (${warehouse}) | Memuat...`;

    let myModal = new bootstrap.Modal(
        document.getElementById("modalDetailGrade"),
    );
    myModal.show();

    document.getElementById("modalGradeContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Mengambil data anomali per Grade...</div></div>';

    $.get(
        `/oracle-fisik/get-detail-grade?grade=${grade}&warehouse=${warehouse}`,
    )
        .done(function (res) {
            document.getElementById("modalGradeContent").innerHTML = res.html;

            let totalPcs = res.summary.total_pcs.toLocaleString("id-ID");

            // PERBAIKAN: Format string sesuai request (Hapus teks "dari X SKU")
            titleEl.innerHTML = `GRADE: <span class="text-warning">${gradeLabel}</span> (${warehouse}) &nbsp;|&nbsp; ${totalPcs} Pcs &nbsp;|&nbsp; <span class="text-danger">SKU Minus (-) ${res.summary.minus_sku}</span> &nbsp;&amp;&nbsp; <span class="text-primary">SKU Plus (+) ${res.summary.plus_sku}</span>`;
        })
        .fail(function () {
            document.getElementById("modalGradeContent").innerHTML =
                '<div class="p-4 text-danger text-center fw-bold">Gagal memuat detail Grade.</div>';
            titleEl.innerText = `GRADE: ${gradeLabel} (${warehouse}) | ERROR`;
        });
};

// --- FUNGSI BUKA MODAL PPM (CARD 4) ---
window.openModalPPM = function () {
    // AMBIL GUDANG YANG AKTIF DI SELECT BOX
    let warehouse = document.getElementById("filter-dashboard-wh").value;

    if (!warehouse) {
        alert("Pilih gudang terlebih dahulu.");
        return;
    }

    let myModal = new bootstrap.Modal(
        document.getElementById("modalDetailPPM"),
    );
    myModal.show();

    document.getElementById("modalPPMContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-emerald"></div><div class="mt-2 text-muted">Memuat data matriks PPM untuk gudang ' +
        warehouse +
        "...</div></div>";

    // TAMBAHKAN PARAMETER WAREHOUSE DI SINI
    $.get(
        `/oracle-fisik/get-detail-ppm?warehouse=${encodeURIComponent(warehouse)}`,
    )
        .done(function (res) {
            document.getElementById("modalPPMContent").innerHTML = res;
        })
        .fail(function () {
            document.getElementById("modalPPMContent").innerHTML =
                '<div class="p-4 text-danger text-center fw-bold">Gagal memuat data PPM.</div>';
        });
};
