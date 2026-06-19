let autoRefreshInterval = null;

window.loadFisikDashboardData = function (so_name) {
    if (!so_name) return;

    document.getElementById("gudangPlaceholder").classList.add("d-none");
    let chartsArea = document.getElementById("mainChartsArea");
    chartsArea.classList.remove("d-none");

    // Tampilkan loading di div chart (karena table detail di bawah udah diilangin)
    document.getElementById("varianceChart").innerHTML =
        '<div class="d-flex h-100 w-100 align-items-center justify-content-center"><div class="spinner-border text-primary"></div><div class="ms-3 text-muted fw-bold">Memproses Data SO...</div></div>';
    if (document.getElementById("resumeChart")) {
        document.getElementById("resumeChart").innerHTML =
            '<div class="d-flex h-100 w-100 align-items-center justify-content-center"><div class="spinner-border text-info"></div></div>';
    }
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
        "sum-price-variance",
        "sku-percentage",
        "sku-counted",
        "sku-onhand",
    ].forEach((id) => {
        if (document.getElementById(id))
            document.getElementById(id).innerText = "0";
    });

    $.get(`/dashboard-so/get-comparison?so_name=${encodeURIComponent(so_name)}`)
        .done(function (res) {
            window.globalComparisonData = res.data;

            if (!res.data || res.data.length === 0) {
                document
                    .getElementById("metricCardsArea")
                    .classList.remove("show");
                chartsArea.classList.add("d-none");

                // TAMBAHAN: Sembunyikan juga area summary tables Pattern (OE & OK)
                let summaryArea = document.getElementById("summaryTablesArea");
                if (summaryArea) summaryArea.classList.add("d-none");

                let placeholder = document.getElementById("gudangPlaceholder");
                placeholder.classList.remove("d-none");

                // PERBAIKAN: Ubah text-muted jadi text-white agar teks terlihat jelas
                placeholder.innerHTML = `<div class="d-flex flex-column align-items-center justify-content-center p-5 text-center">
                    <i data-lucide="database-zap" style="width: 64px; height: 64px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <h4 class="text-white fw-bold">Tidak Ada Data</h4>
                    <p class="text-white">Stock Opname <strong class="text-warning">${so_name}</strong> belum memiliki data.</p>
                </div>`;

                if (typeof lucide !== "undefined") lucide.createIcons();
                return;
            }

            document.getElementById("metricCardsArea").classList.add("show");
            document.getElementById("varianceChart").innerHTML = ""; // Clear loading

            if ($("#oe-variance-pcs").length)
                $("#oe-variance-pcs").text(
                    (res.summary.oe_variance_pcs || 0).toLocaleString("id-ID"),
                );
            if ($("#oe-sku-minus").length)
                $("#oe-sku-minus").text(
                    (res.summary.oe_sku_minus || 0).toLocaleString("id-ID"),
                );
            if ($("#oe-sku-plus").length)
                $("#oe-sku-plus").text(
                    (res.summary.oe_sku_plus || 0).toLocaleString("id-ID"),
                );

            if ($("#ok-variance-pcs").length)
                $("#ok-variance-pcs").text(
                    (res.summary.ok_variance_pcs || 0).toLocaleString("id-ID"),
                );
            if ($("#ok-sku-minus").length)
                $("#ok-sku-minus").text(
                    (res.summary.ok_sku_minus || 0).toLocaleString("id-ID"),
                );
            if ($("#ok-sku-plus").length)
                $("#ok-sku-plus").text(
                    (res.summary.ok_sku_plus || 0).toLocaleString("id-ID"),
                );

            let oeVariance = res.summary.oe_variance_pcs ?? 0;
            let okVariance = res.summary.ok_variance_pcs ?? 0;
            if ($("#mix-variance-pcs").length)
                $("#mix-variance-pcs").text(
                    (oeVariance + okVariance).toLocaleString("id-ID"),
                );

            let oeMinus = res.summary.oe_sku_minus ?? 0;
            let okMinus = res.summary.ok_sku_minus ?? 0;
            if ($("#mix-sku-minus").length)
                $("#mix-sku-minus").text(
                    (oeMinus + okMinus).toLocaleString("id-ID"),
                );

            let oePlus = res.summary.oe_sku_plus ?? 0;
            let okPlus = res.summary.ok_sku_plus ?? 0;
            if ($("#mix-sku-plus").length)
                $("#mix-sku-plus").text(
                    (oePlus + okPlus).toLocaleString("id-ID"),
                );

            if ($("#sum-ppm").length) {
                let ppmVal = res.summary.variance_ppm || 0;
                $("#sum-ppm").text(
                    ppmVal.toLocaleString("id-ID", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }),
                );
            }
            if ($("#sku-percentage").length)
                $("#sku-percentage").text(res.summary.sku_percentage || 0);
            if ($("#sku-counted").length)
                $("#sku-counted").text(
                    (res.summary.total_item_appkso || 0).toLocaleString(
                        "id-ID",
                    ),
                );
            if ($("#sku-onhand").length)
                $("#sku-onhand").text(
                    (res.summary.total_item_oracle || 0).toLocaleString(
                        "id-ID",
                    ),
                );

            const formatCompact = (num) => {
                if (num === null || num === undefined) return "0";
                let absNum = Math.abs(num);
                let sign = num < 0 ? "-" : "";
                if (absNum >= 1000000000)
                    return (
                        sign +
                        (absNum / 1000000000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "B"
                    );
                if (absNum >= 1000000)
                    return (
                        sign +
                        (absNum / 1000000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "M"
                    );
                if (absNum >= 1000)
                    return (
                        sign +
                        (absNum / 1000)
                            .toFixed(1)
                            .replace(/\.0$/, "")
                            .replace(".", ",") +
                        "K"
                    );
                return sign + num.toLocaleString("id-ID");
            };

            if ($("#sum-price-variance").length) {
                let val = res.summary.total_price_variance || 0;
                let cClass =
                    val < 0
                        ? "text-danger"
                        : val > 0
                          ? "text-success"
                          : "text-dark";
                $("#sum-price-variance")
                    .removeClass("text-dark text-danger text-success")
                    .addClass(cClass)
                    .text("Rp " + formatCompact(val));
            }

            // TAMBAHKAN INI UNTUK UPDATE SUB-DETAIL OE & OK
            if ($("#price-variance-oe").length) {
                $("#price-variance-oe").text(
                    formatCompact(res.summary.oe_price_variance || 0),
                );
            }
            if ($("#price-variance-ok").length) {
                $("#price-variance-ok").text(
                    formatCompact(res.summary.ok_price_variance || 0),
                );
            }

            if (typeof lucide !== "undefined") lucide.createIcons();

            // UBAH JADI INI:
            window.renderSpeedometer(
                res.summary.progress_scan || 0,
                res.summary.progress_scan_oe || 0,
                res.summary.progress_scan_ok || 0,
            );
            window.renderTotalQtyBarChart(
                res.summary.total_oracle || 0,
                res.summary.total_appkso || 0,
            );
            window.renderVarianceChart(res.data);
            window.renderResumeChart(res.summary.resume_data);
            window.renderSummaryTables(res.data);

            setTimeout(function () {
                if (typeof mySpeedometerChart !== "undefined")
                    mySpeedometerChart.resize();
                if (typeof myTotalQtyChart !== "undefined")
                    myTotalQtyChart.resize();
                if (typeof myVarianceChart !== "undefined")
                    myVarianceChart.resize();
                if (typeof myResumeChart !== "undefined")
                    myResumeChart.resize();
            }, 300);
        })
        .fail(function () {
            alert("Gagal mengambil data dari Server!");
        });
};

const formatRibuan = (val) => {
    if (val === undefined || val === null) return 0;
    return Math.abs(val)
        .toString()
        .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

window.renderSpeedometer = function (accuracyRate, oeRate, okRate) {
    let chartDom = document.getElementById("speedometerChart");
    if (!chartDom) return;

    // 1. Ambil nilai asli (bisa > 100)
    let rateValue = parseFloat(accuracyRate) || 0;

    // 2. Untuk visualisasi: kalau > 100, kita batasi di 100 biar gak luber
    let visualHeight = Math.min(rateValue, 100);

    // 3. Format display: pakai 2 digit di belakang koma (atau 3 sesuai selera lu)
    let displayRate = rateValue.toFixed(2);

    // Logika Warna (tetap hijau kalau >= 100)
    let colorAcc =
        rateValue >= 100 ? "#10b981" : rateValue >= 80 ? "#f59e0b" : "#ef4444";
    let shadowAcc =
        rateValue >= 100
            ? "rgba(16, 185, 129, 0.4)"
            : rateValue >= 80
              ? "rgba(245, 158, 11, 0.4)"
              : "rgba(239, 68, 68, 0.4)";

    // Inject HTML Baterai Vertikal
    chartDom.innerHTML = `
        <div class="d-flex flex-column align-items-center justify-content-center w-100 h-100">
            <div class="d-flex flex-column align-items-center">
                <div style="width: 20px; height: 8px; background: #cbd5e1; border-radius: 4px 4px 0 0; margin-bottom: 2px;"></div>
                <div style="width: 50px; height: 150px; border: 4px solid #cbd5e1; border-radius: 10px; padding: 3px; position: relative; background: #f8fafc; overflow: hidden; display: flex; align-items: flex-end;">
                    <div id="battery-fill" style="width: 100%; height: 0%; background: linear-gradient(to top, ${colorAcc}, ${colorAcc}cc); border-radius: 4px; transition: height 1.5s ease-out; box-shadow: 0 0 15px ${shadowAcc};"></div>
                </div>
            </div>

            <div class="mt-2 fw-bold" style="color: ${colorAcc}; font-size: 18px;">
                 ${displayRate}%
            </div>
            <div class="mt-1 d-flex flex-column align-items-center" style="font-size: 10px; line-height: 1.6;">
                <span style="color: #68a00e;">OE: ${parseFloat(oeRate || 0).toFixed(2)}%</span>
                <span style="color: #06b6d4;">OK: ${parseFloat(okRate || 0).toFixed(2)}%</span>
            </div>
        </div>
    `;

    // Trigger Animasi (pindah ke tinggi visualHeight)
    setTimeout(() => {
        let fillEl = document.getElementById("battery-fill");
        if (fillEl) fillEl.style.height = visualHeight + "%";
    }, 100);
};

// 3. BAR CHART UNTUK TOTAL QTY (Overlapping / Tumpuk Tengah)
window.renderTotalQtyBarChart = function (totalOracle, totalAppkso) {
    let chartDom = document.getElementById("totalQtyChart");
    if (!chartDom) return;
    window.myTotalQtyChart =
        echarts.getInstanceByDom(chartDom) || echarts.init(chartDom);

    const variance = totalAppkso - totalOracle;

    const option = {
        grid: {
            left: "-25%",
            right: "5%",
            bottom: "5%",
            top: "0%",
            containLabel: true,
        },
        tooltip: {
            trigger: "item",
            formatter: (p) =>
                `<div style="text-align:center;"><b>${p.seriesName}</b><br/>${formatRibuan(p.value)} Pcs</div>`,
        },
        // TRIK 1: Bikin 2 sumbu X yang posisinya numpuk
        xAxis: [
            { type: "category", data: [""], show: false }, // Sumbu X ke-1 (Buat On-hand & Counted)
            { type: "category", data: [""], show: false }, // Sumbu X ke-2 (Buat Overlay Variance)
        ],
        yAxis: {
            type: "value",
            show: false,
            splitLine: { show: false },
        },
        series: [
            {
                name: "Counted",
                type: "bar",
                xAxisIndex: 0, // Pakai sumbu X pertama
                barWidth: "100%",
                itemStyle: {
                    // Gradasi Biru
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: "#10b981" }, // Biru Muda
                        { offset: 1, color: "#059669" }, // Biru Tua
                    ]),
                    borderColor: "#355b96",
                    borderWidth: 1,
                    borderRadius: [6, 6, 0, 0],
                },
                label: {
                    show: true,
                    position: "insideTop",
                    distance: 15,
                    formatter: (p) => `Counted :\n${formatRibuan(p.value)}`,
                    color: "#000",
                    fontWeight: "bold",
                    fontSize: 13,
                    lineHeight: 20,
                },
                data: [totalAppkso],
            },

            {
                name: "On-hand",
                type: "bar",
                xAxisIndex: 0, // Pakai sumbu X pertama
                barWidth: "100%",
                barGap: "0%", // TRIK 2: Kasih 0% biar nempel sama bar sebelahnya
                itemStyle: {
                    // Gradasi Hijau
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: "#3b82f6" }, // Hijau Muda
                        { offset: 1, color: "#2563eb" }, // Hijau Tua
                    ]),
                    borderColor: "#5e8a2f",
                    borderWidth: 1,
                    borderRadius: [6, 6, 0, 0],
                },
                label: {
                    show: true,
                    position: "insideTop", // Teks di dalam pucuk batang
                    distance: 15,
                    formatter: (p) => `On-hand :\n${formatRibuan(p.value)}`,
                    color: "#000",
                    fontWeight: "bold",
                    fontSize: 13,
                    lineHeight: 20,
                },
                data: [totalOracle],
            },

            {
                name: "Variance",
                type: "bar",
                xAxisIndex: 1,
                barWidth: "38%",
                itemStyle: {
                    // Gradasi Oranye/Coklat
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        {
                            offset: 0,
                            color: variance < 0 ? "#f87171" : "#fbbf24",
                        }, // Merah/Oranye Muda
                        {
                            offset: 1,
                            color: variance < 0 ? "#dc2626" : "#d97706",
                        }, // Merah/Oranye Tua
                    ]),
                    borderColor: variance < 0 ? "#991b1b" : "#92400e",
                    borderWidth: 1,
                    borderRadius: [6, 6, 0, 0],
                },
                label: {
                    show: true,
                    position: "top", // Ini kuncinya buat naruh di atas
                    distance: 1, // Jarak teks ke ujung batang
                    formatter: (p) => `Variance :\n${formatRibuan(p.value)}`,
                    color: "#000",
                    fontWeight: "900",
                    fontSize: 13,
                    lineHeight: 18,
                    // TRIK: Ini biar label gak terpotong canvas
                    overflow: "break",
                    backgroundColor: "rgba(255,255,255,0.7)", // Biar teks kebaca meski nabrak background
                    padding: [2, 4],
                    borderRadius: 4,
                },
                // TRIK: Tambahkan ini supaya label bisa muncul di luar area grafik
                emphasis: {
                    label: { show: true },
                },
                data: [variance],
            },
        ],
    };

    window.myTotalQtyChart.setOption(option, true);
};

// 4. CHART TOP 10 (Gradient Mewah)
window.renderVarianceChart = function (data) {
    let chartDom = document.getElementById("varianceChart");
    if (!chartDom) return;

    // 1. Bersihkan chart lama dengan benar sebelum init ulang
    let myChart = echarts.getInstanceByDom(chartDom);
    if (myChart) {
        myChart.dispose();
    }

    // 2. Cek data: kalau kosong, bersihkan saja dan return
    if (!data || data.length === 0) {
        chartDom.innerHTML =
            '<div class="text-center text-muted p-5">Tidak ada data selisih.</div>';
        return;
    }

    // 3. Inisialisasi ulang
    myChart = echarts.init(chartDom);

    // 4. Proses data (tetap pakai logikamu)
    let problematicItems = data.filter((d) => parseInt(d.variance) !== 0);
    problematicItems.sort(
        (a, b) => Math.abs(b.variance) - Math.abs(a.variance),
    );
    let top10 = problematicItems.slice(0, 10).reverse();

    let categories = top10.map((d) => `${d.pattern} | ${d.grade}`);
    let values = top10.map((d) => parseInt(d.variance));

    const option = {
        title: {
            text: "Top 10 Pattern Variance (PCS)",
            left: "center",
            textStyle: { fontSize: 14, color: "#334155", fontWeight: "900" },
        },
        tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
        grid: {
            left: "2%",
            right: "5%",
            bottom: "5%",
            top: "15%",
            containLabel: true,
        },
        xAxis: { type: "value", splitLine: { lineStyle: { type: "dashed" } } },
        yAxis: {
            type: "category",
            data: categories,
            axisLabel: {
                fontSize: 11,
                fontWeight: "bold",
                width: 250,
                overflow: "break",
            },
        },
        series: [
            {
                type: "bar",
                barWidth: "60%",
                data: values.map((v) => ({
                    value: v,
                    itemStyle: {
                        borderRadius: [0, 4, 4, 0],
                        color: new echarts.graphic.LinearGradient(0, 0, 1, 0, [
                            { offset: 0, color: v < 0 ? "#f87171" : "#34d399" },
                            { offset: 1, color: v < 0 ? "#dc2626" : "#059669" },
                        ]),
                    },
                })),
                label: {
                    show: true,
                    position: "right",
                    formatter: (p) =>
                        (p.value > 0 ? "+" : "") + p.value.toLocaleString(),
                    fontWeight: "bold",
                },
            },
        ],
    };

    myChart.setOption(option);

    // Pastikan off dulu sebelum on, ini sudah benar
    myChart.off("click");
    myChart.on("click", function (params) {
        // Cek dulu apakah params.name ada isinya
        if (!params.name) return;

        let splitName = params.name.split(" | ");

        // Log buat debugging di console (kalau gak jalan, cek F12)
        console.log("Clicked:", splitName);

        if (splitName.length >= 2) {
            window.openModalDetailPattern(
                splitName[0].trim(),
                splitName[1].trim(),
            );
        } else {
            // Kalau grade gak ada, kirim pattern aja atau handle sebagai 'ALL'
            window.openModalDetailPattern(splitName[0].trim(), "ALL");
        }
    });
};

// ==============================================
// LOGIC MODALS - FETCH DATA
// ==============================================

window.openModalGrade = function (grade) {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name) return alert("Pilih SO Active dulu.");

    let titleEl = document.getElementById("modalTitleGrade");
    let gradeLabel = grade === "MIX" ? "OE + OK" : grade;

    // Ubah teks saat loading
    titleEl.innerHTML = `GRADE: <span class="text-warning">${gradeLabel}</span> &nbsp;||&nbsp; Variance: Menghitung...`;

    new bootstrap.Modal(document.getElementById("modalDetailGrade")).show();
    document.getElementById("modalGradeContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-dark"></div></div>';

    $.get(
        `/dashboard-so/get-detail-pattern?so_name=${encodeURIComponent(so_name)}&grade=${grade}&ctx=grd`,
    )
        .done(function (res) {
            document.getElementById("modalGradeContent").innerHTML = res.html;

            // Format angka (+/-)
            let varSign = res.total_variance > 0 ? "+" : "";
            let varFormat =
                varSign + res.total_variance.toLocaleString("id-ID");

            // Ubah teks saat sukses
            titleEl.innerHTML = `GRADE: <span class="text-warning">${gradeLabel}</span> &nbsp;||&nbsp; Variance: ${varFormat} PCS`;
        })
        .fail(
            () =>
                (document.getElementById("modalGradeContent").innerHTML =
                    '<div class="p-4 text-danger text-center">Gagal memuat.</div>'),
        );
};

window.openModalDetailPattern = function (pattern, grade) {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name) return;

    let titleEl = document.getElementById("modalTitlePattern");

    // Ubah teks saat loading
    titleEl.innerHTML = `PATTERN: <span class="text-warning">${pattern} (${grade})</span> &nbsp;||&nbsp; Variance: Menghitung...`;

    new bootstrap.Modal(document.getElementById("modalDetailPattern")).show();
    document.getElementById("modalDetailContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-dark"></div></div>';

    $.get(
        `/dashboard-so/get-detail-pattern?so_name=${encodeURIComponent(so_name)}&pattern=${encodeURIComponent(pattern)}&grade=${grade}&ctx=ptn`,
    )
        .done(function (res) {
            document.getElementById("modalDetailContent").innerHTML = res.html;

            // Format angka (+/-)
            let varSign = res.total_variance > 0 ? "+" : "";
            let varFormat =
                varSign + res.total_variance.toLocaleString("id-ID");

            // Ubah teks saat sukses
            titleEl.innerHTML = `PATTERN: <span class="text-warning">${pattern} (${grade})</span> &nbsp;||&nbsp; Variance: ${varFormat} PCS`;
        })
        .fail(
            () =>
                (document.getElementById("modalDetailContent").innerHTML =
                    '<div class="p-4 text-danger text-center">Gagal memuat.</div>'),
        );
};

window.openModalPriceVariance = function () {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name || !window.globalComparisonData) return;

    new bootstrap.Modal(document.getElementById("modalPriceVariance")).show();
    let tbody = document.getElementById("tbody-price-variance-rekap");

    document.getElementById("priceDetailContainer").classList.add("d-none");
    document.getElementById("priceDetailContainer").innerHTML = "";

    // Hapus container net-zero lama kalau ada
    let old = document.getElementById("net-zero-variance-container");
    if (old) old.remove();

    // Gabungkan: variance PCS != 0 ATAU (variance PCS == 0 tapi ada SKU bermasalah)
    let filteredData = window.globalComparisonData.filter(
        (i) =>
            parseInt(i.variance) !== 0 ||
            parseInt(i.sku_minus) > 0 ||
            parseInt(i.sku_plus) > 0,
    );

    if (filteredData.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="10" class="text-center p-4 text-muted">Aman, tidak ada selisih.</td></tr>';
        return;
    }

    // Sort: absolute variance PCS terbesar dulu, net zero paling bawah
    filteredData.sort((a, b) => {
        let varA = Math.abs(parseInt(a.variance));
        let varB = Math.abs(parseInt(b.variance));
        if (varB !== varA) return varB - varA;
        // Jika sama-sama 0, sort by total SKU bermasalah
        return (
            parseInt(b.sku_minus) +
            parseInt(b.sku_plus) -
            (parseInt(a.sku_minus) + parseInt(a.sku_plus))
        );
    });

    tbody.innerHTML = filteredData
        .map((row, index) => {
            let isNetZero = parseInt(row.variance) === 0;
            let varClass =
                row.variance < 0
                    ? "text-danger"
                    : row.variance > 0
                      ? "text-primary"
                      : "text-muted";
            let varRpClass =
                row.variance_rp < 0
                    ? "text-danger"
                    : row.variance_rp > 0
                      ? "text-primary"
                      : "text-muted";

            // Baris net zero dikasih background ungu muda + badge
            let rowStyle = isNetZero
                ? 'style="cursor:pointer; background-color: rgba(124,58,237,0.07);"'
                : 'style="cursor:pointer; transition: 0.2s;"';

            let patternCell = isNetZero
                ? `${row.pattern} <span class="badge bg-secondary ms-1">${row.grade}</span> <span class="badge ms-1" style="background-color:#7c3aed; font-size:9px;">NET ZERO</span>`
                : `${row.pattern} <span class="badge bg-secondary ms-1">${row.grade}</span>`;

            return `
        <tr class="clickable-price-row" onclick="window.loadPriceVarianceDetail('${row.pattern}', '${row.grade}', this)" ${rowStyle}>
            <td>${index + 1}</td>
            <td class="text-start fw-bold text-dark ps-3">${patternCell}</td>
            <td class="text-end">${parseInt(row.qty_appkso).toLocaleString("id-ID")}</td>
            <td class="text-end">${parseInt(row.appkso_rp).toLocaleString("id-ID")}</td>
            <td class="text-end">${parseInt(row.qty_oracle).toLocaleString("id-ID")}</td>
            <td class="text-end">${parseInt(row.oracle_rp).toLocaleString("id-ID")}</td>
            <td class="text-end fw-bold ${varClass}">${row.variance > 0 ? "+" : ""}${parseInt(row.variance).toLocaleString("id-ID")}</td>
            <td class="text-end fw-bold ${varRpClass}">${row.variance_rp > 0 ? "+" : ""}${parseInt(row.variance_rp).toLocaleString("id-ID")}</td>
            <td class="text-center text-danger fw-bold">${row.sku_minus}</td>
            <td class="text-center text-primary fw-bold">${row.sku_plus}</td>
        </tr>`;
        })
        .join("");
};

// --- FUNGSI BARU UNTUK MENGELUARKAN TABEL KIRI KANAN ---
window.loadPriceVarianceDetail = function (pattern, grade, rowElement) {
    let so_name = document.getElementById("filter-dashboard-so").value;
    let container = document.getElementById("priceDetailContainer");

    // Highlight baris yang sedang diklik biar user tau posisinya
    document
        .querySelectorAll(".clickable-price-row")
        .forEach((tr) => (tr.style.backgroundColor = ""));
    if (rowElement) rowElement.style.backgroundColor = "#f1f5f9";

    container.classList.remove("d-none");
    container.innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-rose"></div><div class="mt-2 fw-bold text-muted">Memuat Detail...</div></div>';

    $.get(
        `/dashboard-so/get-detail-price-pattern?so_name=${encodeURIComponent(so_name)}&pattern=${encodeURIComponent(pattern)}&grade=${grade}`,
    )
        .done(function (res) {
            container.innerHTML = res.html;
        })
        .fail(function () {
            container.innerHTML =
                '<div class="alert alert-danger text-center">Gagal memuat detail harga.</div>';
        });
};

window.openModalPPM = function () {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name) return;

    new bootstrap.Modal(document.getElementById("modalDetailPPM")).show();
    document.getElementById("modalPPMContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-success"></div></div>';

    $.get(`/dashboard-so/get-detail-ppm?so_name=${encodeURIComponent(so_name)}`)
        .done(function (res) {
            document.getElementById("modalPPMContent").innerHTML = res;
        })
        .fail(
            () =>
                (document.getElementById("modalPPMContent").innerHTML =
                    '<div class="p-4 text-danger text-center">Gagal memuat PPM.</div>'),
        );
};

window.openModalUnscanned = function () {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name) return;

    new bootstrap.Modal(document.getElementById("modalUnscannedSKU")).show();

    document.getElementById("tbody-unscanned-oe").innerHTML = "";
    document.getElementById("tbody-unscanned-ok").innerHTML = "";
    document.getElementById("searchUnscanned").value = "";
    document.getElementById("summary-oe-badge").innerText = "";
    document.getElementById("summary-ok-badge").innerText = "";
    document.getElementById("count-oe").innerText = "0 SKU";
    document.getElementById("count-ok").innerText = "0 SKU";

    // Simpan data global untuk dipakai filter
    window._unscannedDataOE = [];
    window._unscannedDataOK = [];

    // Render placeholder "pilih filter dulu"
    const placeholderMsg = (grade) =>
        `<tr><td colspan="7" class="text-center text-muted py-4">
            <i class="text-warning">⚠️</i> Pilih filter di atas untuk menampilkan data <strong>${grade}</strong>.
         </td></tr>`;

    document.getElementById("tbody-unscanned-oe").innerHTML =
        placeholderMsg("OE");
    document.getElementById("tbody-unscanned-ok").innerHTML =
        placeholderMsg("OK");

    // Inject filter bar jika belum ada
    if (!document.getElementById("filter-progress-bar")) {
        let filterHtml = `
        <div id="filter-progress-bar" class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <span class="fw-bold text-muted" style="font-size:12px;">Filter Progress:</span>
            <button class="btn btn-sm btn-outline-danger fw-bold filter-progress-btn" data-filter="kurang" onclick="window.applyProgressFilter('kurang')">
                📉 Kurang Scan <span class="badge bg-danger ms-1">&lt; 100%</span>
            </button>
            <button class="btn btn-sm btn-outline-success fw-bold filter-progress-btn" data-filter="pas" onclick="window.applyProgressFilter('pas')">
                ✅ Scan Sesuai <span class="badge bg-success ms-1">= 100%</span>
            </button>
            <button class="btn btn-sm btn-outline-primary fw-bold filter-progress-btn" data-filter="lebih" onclick="window.applyProgressFilter('lebih')">
                📈 Lebih Scan <span class="badge bg-primary ms-1">&gt; 100%</span>
            </button>
            <button class="btn btn-sm btn-outline-secondary fw-bold filter-progress-btn" data-filter="all" onclick="window.applyProgressFilter('all')">
                🔁 Tampilkan Semua
            </button>
        </div>`;

        let searchEl = document.getElementById("searchUnscanned");
        searchEl.insertAdjacentHTML("afterend", filterHtml);
    }

    // Fetch data
    $.get(
        `/dashboard-so/get-unscanned-items?so_name=${encodeURIComponent(so_name)}`,
    )
        .done(function (res) {
            window._unscannedDataOE = res.data.filter((i) => i.grade === "OE");
            window._unscannedDataOK = res.data.filter((i) => i.grade === "OK");

            // Hitung summary badge
            const calcSummary = (arr) => {
                let sumO = arr.reduce((a, b) => a + b.qty_oracle, 0);
                let sumC = arr.reduce((a, b) => a + b.qty_counted, 0);
                let pct = sumO > 0 ? ((sumC / sumO) * 100).toFixed(2) : "0.00";
                return { sumO, sumC, pct };
            };

            let sOE = calcSummary(window._unscannedDataOE);
            let sOK = calcSummary(window._unscannedDataOK);

            document.getElementById("summary-oe-badge").innerText =
                `OE: ${parseInt(sOE.sumC).toLocaleString("id-ID")} / ${parseInt(sOE.sumO).toLocaleString("id-ID")} Pcs (${sOE.pct}%)`;
            document.getElementById("summary-ok-badge").innerText =
                `OK: ${parseInt(sOK.sumC).toLocaleString("id-ID")} / ${parseInt(sOK.sumO).toLocaleString("id-ID")} Pcs (${sOK.pct}%)`;
        })
        .fail(function () {
            document.getElementById("tbody-unscanned-oe").innerHTML =
                '<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>';
            document.getElementById("tbody-unscanned-ok").innerHTML =
                '<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>';
        });
};

window.applyProgressFilter = function (filterType) {
    document.querySelectorAll(".filter-progress-btn").forEach((btn) => {
        btn.classList.remove("active");
        if (btn.dataset.filter === filterType) btn.classList.add("active");
    });

    const filterFn = (arr) => {
        if (filterType === "kurang") return arr.filter((i) => i.qty_sisa < 0);
        if (filterType === "pas") return arr.filter((i) => i.qty_sisa === 0);
        if (filterType === "lebih") return arr.filter((i) => i.qty_sisa > 0);
        return arr;
    };

    let filteredOE = filterFn(window._unscannedDataOE);
    let filteredOK = filterFn(window._unscannedDataOK);

    filteredOE.sort((a, b) => b.qty_sisa - a.qty_sisa);
    filteredOK.sort((a, b) => b.qty_sisa - a.qty_sisa);

    const renderRows = (arr) => {
        if (!arr.length)
            return '<tr><td colspan="7" class="text-center text-muted py-3">Tidak ada data untuk filter ini.</td></tr>';

        return arr
            .map((row, idx) => {
                const persen = parseFloat(row.persen);
                const persenFmt = persen.toFixed(2);
                const isLebih = row.qty_sisa > 0;
                const isPas = row.qty_sisa === 0;
                const isZero = row.qty_counted === 0;

                const barColor = isLebih
                    ? "#8b5cf6"
                    : isPas
                      ? "#10b981"
                      : persen >= 75
                        ? "#f59e0b"
                        : persen >= 50
                          ? "#3b82f6"
                          : "#ef4444";

                const barWidth = Math.min(Math.max(persen, 0), 100);

                // Warna teks kolom SISA — pakai inline style bukan class biar aman
                const sisaColor =
                    row.qty_sisa < 0
                        ? "#8b5cf6"
                        : row.qty_sisa === 0
                          ? "#10b981"
                          : "#ef4444";

                const rowBg = isLebih
                    ? "rgba(139,92,246,0.08)"
                    : isPas
                      ? "rgba(16,185,129,0.08)"
                      : isZero
                        ? "rgba(239,68,68,0.08)"
                        : "transparent";

                const descEscaped = (row.description || "")
                    .replace(/'/g, "\\'")
                    .replace(/"/g, "&quot;");

                // Baris lebih scan bisa diklik untuk lihat scan history
                const rowOnclick = `onclick="window.openModalScanHistory('${row.item}', '${descEscaped}')" style="background-color:${rowBg}; cursor:pointer;" title="Klik untuk lihat detail scan"`;

                const lebihBadge = isLebih
                    ? `<span class="badge ms-1" style="background-color:#8b5cf6; font-size:9px;">LEBIH ▶</span>`
                    : "";

                return `<tr ${rowOnclick}>
    <td class="text-center text-muted" style="font-size:11px;">${idx + 1}</td>
    <td class="fw-bold text-primary" style="font-size:11px;">${row.item}${lebihBadge}</td>
    <td style="font-size:11px; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${row.description || ""}">${row.description || "-"}</td>

    <td class="text-end fw-semibold text-primary" style="font-size:11px;">${parseInt(row.qty_counted).toLocaleString("id-ID")}</td>
    <td class="text-end fw-semibold" style="font-size:11px;">${parseInt(row.qty_oracle).toLocaleString("id-ID")}</td>

    <td class="text-end fw-bold" style="font-size:11px; color:${sisaColor};">${parseInt(row.qty_sisa).toLocaleString("id-ID")}</td>

    <td>
        <div class="progress position-relative" style="height:18px; border-radius:5px; background-color:#e2e8f0;">
            <div class="progress-bar" role="progressbar"
                style="width:${barWidth}%; background-color:${barColor}; transition:width 0.4s;">
            </div>
            <span class="position-absolute w-100 text-center fw-bold"
                style="font-size:10px; line-height:18px; color:#1e293b; mix-blend-mode:multiply;">
                ${persenFmt}%
            </span>
        </div>
    </td>
</tr>`;
            })
            .join("");
    };

    document.getElementById("tbody-unscanned-oe").innerHTML =
        renderRows(filteredOE);
    document.getElementById("tbody-unscanned-ok").innerHTML =
        renderRows(filteredOK);
    document.getElementById("count-oe").innerText = filteredOE.length + " SKU";
    document.getElementById("count-ok").innerText = filteredOK.length + " SKU";
    document.getElementById("searchUnscanned").value = "";
};

window.filterUnscannedTable = function () {
    let input = document.getElementById("searchUnscanned").value.toLowerCase();
    ["tbody-unscanned-oe", "tbody-unscanned-ok"].forEach((id) => {
        let rows = document.getElementById(id).getElementsByTagName("tr");
        for (let i = 0; i < rows.length; i++) {
            let cells = rows[i].cells;
            if (!cells || cells.length < 3) continue;
            let itemText = cells[1] ? cells[1].innerText.toLowerCase() : "";
            let descText = cells[2] ? cells[2].innerText.toLowerCase() : "";
            rows[i].style.display =
                itemText.includes(input) || descText.includes(input)
                    ? ""
                    : "none";
        }
    });
};

// ==============================================
// 5. RENDER TABEL COMPARISON PER PATTERN (KIRI OE, KANAN OK)
// ==============================================
window.renderComparisonTable = function (data) {
    let container = document.getElementById("tableContainerFisik");
    if (!data || data.length === 0) {
        container.innerHTML =
            '<div class="text-center text-muted p-4">Tidak ada data untuk SO ini.</div>';
        return;
    }

    let html = '<div class="row g-3">';

    // Looping untuk membagi dua tabel (OE di kiri, OK di kanan)
    ["OE", "OK"].forEach((grade) => {
        let filtered = data.filter(
            (i) =>
                i.grade == grade &&
                (parseInt(i.qty_appkso) > 0 || parseInt(i.qty_oracle) > 0),
        );

        if (filtered.length === 0) return;

        // SORTING: Sesuai Variance (Absolute Terbesar), Lalu Sesuai SKU (Minus + Plus Terbesar)
        filtered.sort((a, b) => {
            let varA = Math.abs(parseInt(a.variance));
            let varB = Math.abs(parseInt(b.variance));
            if (varB !== varA) {
                return varB - varA; // Descending Variance
            }
            // Jika Variance sama, sort berdasarkan jumlah anomali SKU
            let skuA = parseInt(a.sku_minus) + parseInt(a.sku_plus);
            let skuB = parseInt(b.sku_minus) + parseInt(b.sku_plus);
            return skuB - skuA;
        });

        html += `
        <div class="col-12 col-xl-6">
            <div class="table-responsive border rounded bg-white shadow-sm custom-scroll" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="sticky-top text-white" style="font-size: 11px; z-index: 1; background-color: #1e293b;">
                        <tr>
                            <th class="ps-3 py-2">Pattern (${grade})</th>
                            <th class="text-end py-2">Counted</th>
                            <th class="text-end py-2">On-hand</th>
                            <th class="text-end py-2">Variance</th>
                            <th class="text-center py-2">SKU (-)</th>
                            <th class="text-center py-2">SKU (+)</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 11.5px;">
                        ${filtered
                            .map((row) => {
                                let varClass =
                                    row.variance < 0
                                        ? "text-danger"
                                        : row.variance > 0
                                          ? "text-success"
                                          : "text-muted";
                                let varSign = row.variance > 0 ? "+" : "";
                                let skuMin =
                                    row.sku_minus > 0 ? row.sku_minus : "-";
                                let skuPlus =
                                    row.sku_plus > 0 ? row.sku_plus : "-";

                                return `
                            <tr onclick="window.openModalDetailPattern('${row.pattern}', '${row.grade}')" style="cursor:pointer; transition: 0.2s;">
                                <td class="text-start fw-bold text-dark ps-3">${row.pattern}</td>
                                <td class="text-end fw-semibold">${parseInt(row.qty_appkso).toLocaleString("id-ID")}</td>
                                <td class="text-end fw-semibold">${parseInt(row.qty_oracle).toLocaleString("id-ID")}</td>
                                <td class="text-end fw-black ${varClass}">${varSign}${parseInt(row.variance).toLocaleString("id-ID")}</td>
                                <td class="text-center text-danger fw-bold">${skuMin}</td>
                                <td class="text-center text-success fw-bold">${skuPlus}</td>
                            </tr>
                            `;
                            })
                            .join("")}
                    </tbody>
                </table>
            </div>
        </div>`;
    });

    container.innerHTML = html + "</div>";
};

// ==============================================
// 6. FUNGSI PRINT MODAL (Universal - Kiri Kanan Mentok & 1 Baris)
// ==============================================
window.printModal = function (titleId, contentId) {
    let title = document.getElementById(titleId).innerText;
    let content = document.getElementById(contentId).innerHTML;

    let printWindow = window.open("", "", "height=800,width=1200");

    if (!printWindow) {
        alert(
            "Gagal membuka jendela cetak. Mohon periksa apakah pop-up diblokir oleh browser.",
        );
        return;
    }

    // Cek apakah yang di-print adalah modal Grade atau Detail Pattern
    let hidePatternCss = "";
    if (
        contentId === "modalGradeContent" ||
        contentId === "modalDetailContent"
    ) {
        hidePatternCss = `
            /* Sembunyikan kolom ke-2 (PATTERN) di tabel UTAMA saja, jangan kena tabel similar */
            .table-responsive > table > thead > tr > th:nth-child(2),
            .table-responsive > table > tbody > tr > td:nth-child(2) {
                display: none !important;
            }

            /* PAKSA TAMPILKAN BARIS SIMILAR SAAT PRINT */
            tr.similar-collapse-row {
                display: table-row !important;
            }

            /* SEMBUNYIKAN BADGE 'S' KARENA DATA SUDAH OTOMATIS TAMPIL */
            .similar-badge {
                display: none !important;
            }
        `;
    }

    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Print Document</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                /* Kertas A4 Portrait, margin kertas DIHILANGKAN TOTAL */
                @page {
                    size: A4 portrait;
                    margin: 0mm !important;
                }

                /* Body tanpa celah sedikitpun */
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                    background-color: white;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                .card-header span, .card-header .badge {
                    font-size: 8px !important;
                    padding: 2px 4px !important;
                }

                /* Matikan paksaan ukuran class fs-6 dari Bootstrap */
                .fs-6 {
                    font-size: 8px !important;
                }


                /* Hapus batas scroll */
                .table-responsive {
                    max-height: none !important;
                    overflow: visible !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                /* ========================================================= */
                /* PAKSA LAYOUT 50:50 MENTOK KIRI KANAN */
                /* ========================================================= */
                .row {
                    display: flex !important;
                    flex-wrap: nowrap !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                /* Masing-masing tabel pas 50% */
                .row > div {
                    width: 50% !important;
                    flex: 0 0 50% !important;
                    max-width: 50% !important;
                    padding: 0 2px !important; /* Celah tengah antar 2 tabel saja */
                }

                /* Warna Asli Header */
                .bg-danger { background-color: #dc3545 !important; color: white !important;}
                .bg-primary { background-color: #0d6efd !important; color: white !important;}
                .bg-light { background-color: #f8f9fa !important; color: black !important;}

                /* ========================================================= */
                /* PAKSA TEKS JADI 1 BARIS & FONT DIKECILKAN */
                /* ========================================================= */
                table {
                    width: 100% !important;
                    table-layout: auto !important;
                }

                table td, table th {
                    border: 1px solid #dee2e6 !important;
                    vertical-align: middle !important;
                    padding: 2px 3px !important; /* Makin tipis biar irit tempat */
                    white-space: nowrap !important; /* KUNCI: PAKSA 1 BARIS */
                    overflow: hidden !important;
                    font-size: 7.5px !important; /* FONT DIKECILIN BIAR MUAT */
                }

                /* Hilangkan styling UI bayangan, border lengkung, & padding bawaan Card */
                .shadow, .shadow-sm { box-shadow: none !important; }
                .card { border: none !important; margin: 0 !important; border-radius: 0 !important; }
                .card-header { padding: 4px !important; border-radius: 0 !important; }
                .card-body, .p-3 { padding: 0 !important; }
                .border-0 { border: 1px solid #dee2e6 !important; }

                ${hidePatternCss}
            </style>
        </head>
        <body>
            <h4 class="fw-bold pb-1" style="border-bottom: 2px solid #000; color: #000; margin: 5px; font-size: 14px;">${title}</h4>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Tunggu 800ms biar Bootstrap CSS ke-load sempurna baru munculin dialog Print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 800);
};

window.openModalScanHistory = function (itemCode, description) {
    let so_name = document.getElementById("filter-dashboard-so").value;
    if (!so_name) return;

    let titleEl = document.getElementById("modalTitleScanHistory");
    titleEl.innerHTML = `DETAIL SCAN: <span class="text-warning">${itemCode}</span> <span class="fw-normal fs-6">| ${description}</span>`;

    let modalEl = document.getElementById("modalScanHistory");
    let myModal = new bootstrap.Modal(modalEl);

    myModal.show();

    // --- TAMBAHAN: Hapus atribut yang bikin error ---
    modalEl.removeAttribute("aria-hidden");
    // Hapus juga dari backdrop kalau ada
    let backdrop = document.querySelector(".modal-backdrop");
    if (backdrop) backdrop.style.zIndex = "1055";

    document.getElementById("modalScanHistoryContent").innerHTML =
        '<div class="text-center p-5"><div class="spinner-border text-dark"></div></div>';

    $.get(
        `/dashboard-so/get-scan-history?so_name=${encodeURIComponent(so_name)}&item_code=${encodeURIComponent(itemCode)}`,
    )
        .done(function (res) {
            document.getElementById("modalScanHistoryContent").innerHTML =
                res.html;
        })
        .fail(function () {
            document.getElementById("modalScanHistoryContent").innerHTML =
                '<div class="p-4 text-danger text-center">Gagal memuat history scan.</div>';
        });
};

// Paksa bersihkan sisa modal pas ditutup
document
    .getElementById("modalScanHistory")
    .addEventListener("hide.bs.modal", function (event) {
        // KUNCI: Pindahkan fokus ke tombol lain (misal ke body) sebelum modal benar-benar tertutup
        document.activeElement.blur();
        document.body.focus();
    });

document
    .getElementById("modalScanHistory")
    .addEventListener("hidden.bs.modal", function () {
        let modalEl = document.getElementById("modalScanHistory");

        // 1. Hapus class modal-open dari body biar bisa scroll lagi
        document.body.classList.remove("modal-open");
        document.body.style.overflow = "";
        document.body.style.paddingRight = "";

        // 2. Hapus atribut aria-hidden yang bikin error
        modalEl.removeAttribute("aria-hidden");

        // 3. Hapus semua backdrop yang tertinggal
        let backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach((b) => b.remove());
    });

window.toggleAutoRefresh = function (btn) {
    let so = document.getElementById("filter-dashboard-so").value;
    if (!so) return;

    // 1. Ambil instance tooltip Bootstrap-nya
    let tooltip = bootstrap.Tooltip.getInstance(btn);

    if (autoRefreshInterval) {
        // --- MATIKAN ---
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;

        btn.style.background = "#fe6807";
        btn.innerHTML = `<i data-lucide="refresh-cw" style="width: 18px; height: 18px;"></i>`;

        // Update teks tooltip jadi "Aktifkan"
        btn.setAttribute(
            "title",
            "Klik untuk aktifkan Refresh Otomatis (5 detik)",
        );
        if (tooltip)
            tooltip.setContent({
                ".tooltip-inner":
                    "Klik untuk aktifkan Refresh Otomatis (5 detik)",
            });

        lucide.createIcons();
    } else {
        // --- AKTIFKAN ---
        let count = 5;

        // Update teks tooltip jadi "Matikan"
        btn.setAttribute("title", "Klik untuk matikan Refresh Otomatis");
        if (tooltip)
            tooltip.setContent({
                ".tooltip-inner": "Klik untuk matikan Refresh Otomatis",
            });

        const updateBtnText = () => {
            btn.innerHTML = `<span style="font-size: 11px; font-weight: 900;">${count}s</span>`;
        };

        window.loadFisikDashboardData(so);
        updateBtnText();
        count--;

        autoRefreshInterval = setInterval(() => {
            if (count <= 0) {
                window.loadFisikDashboardData(so);
                count = 5;
            }
            updateBtnText();
            count--;
        }, 1000);

        btn.style.background = "#10b981";
    }

    // Trik: Hide tooltip setelah diklik biar nggak nutupin button
    if (tooltip) tooltip.hide();
};

// ==============================================
// 7. RENDER TABEL SUMMARY PATTERN (OE & OK) DI BAWAH CHART
// ==============================================
window.renderSummaryTables = function (data) {
    let tbodyOE = document.getElementById("tbody-oe");
    let tbodyOK = document.getElementById("tbody-ok");
    let headerOE = document.getElementById("summary-header-oe");
    let headerOK = document.getElementById("summary-header-ok");

    if (!tbodyOE || !tbodyOK) return;

    document.getElementById("summaryTablesArea").classList.remove("d-none");
    tbodyOE.innerHTML = "";
    tbodyOK.innerHTML = "";

    let sortedData = [...data];

    // Variabel untuk nyimpen totalan
    let totals = {
        OE: { counted: 0, onhand: 0, variance: 0, sku_minus: 0, sku_plus: 0 },
        OK: { counted: 0, onhand: 0, variance: 0, sku_minus: 0, sku_plus: 0 },
    };

    // Sort Data
    sortedData.sort((a, b) => {
        let varA = Math.abs(parseInt(a.variance));
        let varB = Math.abs(parseInt(b.variance));
        if (varB !== varA) return varB - varA;

        let skuA = parseInt(a.sku_minus) + parseInt(a.sku_plus);
        let skuB = parseInt(b.sku_minus) + parseInt(b.sku_plus);
        return skuB - skuA;
    });

    sortedData.forEach((item) => {
        let safePattern = item.pattern
            .replace(/'/g, "\\'")
            .replace(/"/g, "&quot;");

        // Bikin row HTML
        let row = `
        <tr onclick="window.openModalDetailPattern('${safePattern}', '${item.grade}')" style="cursor:pointer; transition: 0.2s;" class="hover-row">
            <td class="fw-bold text-dark text-start">${item.pattern}</td>
            <td class="text-end fw-semibold">${formatRibuan(item.qty_appkso)}</td>
            <td class="text-end fw-semibold">${formatRibuan(item.qty_oracle)}</td>
            <td class="text-end fw-black ${item.variance < 0 ? "text-danger" : item.variance > 0 ? "text-success" : "text-muted"}">${item.variance > 0 ? "+" : ""}${formatRibuan(item.variance)}</td>
            <td class="text-center fw-bold text-danger">${item.sku_minus > 0 ? item.sku_minus : "-"}</td>
            <td class="text-center fw-bold text-success">${item.sku_plus > 0 ? item.sku_plus : "-"}</td>
        </tr>`;

        // Masukin HTML ke tabel & Tambahin ke Totals
        if (item.grade === "OE" || item.grade === "OK") {
            if (item.grade === "OE") tbodyOE.innerHTML += row;
            if (item.grade === "OK") tbodyOK.innerHTML += row;

            totals[item.grade].counted += parseInt(item.qty_appkso) || 0;
            totals[item.grade].onhand += parseInt(item.qty_oracle) || 0;
            totals[item.grade].variance += parseInt(item.variance) || 0;
            totals[item.grade].sku_minus += parseInt(item.sku_minus) || 0;
            totals[item.grade].sku_plus += parseInt(item.sku_plus) || 0;
        }
    });

    // Helper function buat ngerender badge totals
    const renderHeaderStats = (t) => {
        let varClass =
            t.variance < 0
                ? "text-danger"
                : t.variance > 0
                  ? "text-success"
                  : "text-muted";
        let varSign = t.variance > 0 ? "+" : "";
        return `
            <span class="text-primary">Counted: ${formatRibuan(t.counted)}</span> |
            <span class="text-secondary">On-hand: ${formatRibuan(t.onhand)}</span> |
            <span class="${varClass}">Var: ${varSign}${formatRibuan(t.variance)}</span> |
            <span class="text-danger">SKU (-): ${formatRibuan(t.sku_minus)}</span> |
            <span class="text-success">SKU (+): ${formatRibuan(t.sku_plus)}</span>
        `;
    };

    // Cetak ke layar
    if (headerOE) headerOE.innerHTML = renderHeaderStats(totals.OE);
    if (headerOK) headerOK.innerHTML = renderHeaderStats(totals.OK);

    // Kalau kosong, kasih tulisan keterangan
    if (tbodyOE.innerHTML === "")
        tbodyOE.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data pattern OE</td></tr>';
    if (tbodyOK.innerHTML === "")
        tbodyOK.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data pattern OK</td></tr>';
};

window.renderResumeChart = function (resumeData) {
    let chartDom = document.getElementById("resumeChart");
    if (!chartDom) return;

    // Bersihkan dulu kalau ada instance lama
    let existingChart = echarts.getInstanceByDom(chartDom);
    if (existingChart) existingChart.dispose();

    window.myResumeChart = echarts.init(chartDom);

    // Default object jika resumeData kosong
    const defaultData = {
        "OE TIRE": { counted: 0, on_hand: 0, variance: 0 },
        "OE TUBE": { counted: 0, on_hand: 0, variance: 0 },
        "OK TIRE": { counted: 0, on_hand: 0, variance: 0 },
        "OK TUBE": { counted: 0, on_hand: 0, variance: 0 },
    };

    // Merge data dari server dengan default supaya gak error
    let data = { ...defaultData, ...(resumeData || {}) };

    let categories = ["OE TIRE", "OE TUBE", "OK TIRE", "OK TUBE"];
    let countedData = categories.map((k) => data[k].counted);
    let onhandData = categories.map((k) => data[k].on_hand);
    let varianceData = categories.map((k) => data[k].variance);

    const option = {
        // 1 & 4. Geser ke kiri mepet dan naikkan batas bawah supaya nilai minus tidak terpotong
        grid: {
            top: "5%",
            bottom: "10%",
            left: "-25%",
            right: "2%",
            containLabel: true,
        },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
        },
        xAxis: {
            type: "category",
            data: categories,
            position: "top",
            splitLine: {
                show: true,
                lineStyle: {
                    color: "#000", // Warna hitam
                    width: 1, // Ketebalan garis
                    type: "solid",
                },
            },
            axisLabel: {
                fontSize: 11,
                fontWeight: "800",
                color: "#334155",
                interval: 0,
                margin: 10, // Jarak judul pattern ke grafik batangnya
            },
            axisLine: { show: false },
            axisTick: { show: false },
            boundaryGap: true,
        },
        yAxis: {
            type: "value",
            show: false,
            scale: true, // Biar ECharts otomatis menyesuaikan jika ada nilai minus yg jauh
        },
        series: [
            {
                name: "Counted",
                type: "bar",
                // 3. Grafik batang dilebarkan
                barWidth: "35%",
                barGap: "5%", // Celah elegan antara bar ijo dan biru
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: "#10b981" },
                        { offset: 1, color: "#059669" },
                    ]),
                    borderRadius: [4, 4, 0, 0],
                },
                data: countedData,
                label: {
                    show: true,
                    position: "inside",
                    rotate: 90,
                    // 2. Format ribuan (titik) diterapkan
                    formatter: (p) => formatRibuan(p.value),
                    fontSize: 10,

                    color: "#000",
                    align: "start",
                    verticalAlign: "middle",
                },
            },
            {
                name: "On-hand",
                type: "bar",
                // 3. Grafik batang dilebarkan
                barWidth: "35%",
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: "#3b82f6" },
                        { offset: 1, color: "#2563eb" },
                    ]),
                    borderRadius: [4, 4, 0, 0],
                },
                data: onhandData,
                label: {
                    show: true,
                    position: "inside",
                    rotate: 90,
                    // 2. Format ribuan (titik) diterapkan
                    formatter: (p) => formatRibuan(p.value),
                    fontSize: 10,

                    color: "#000",
                    align: "start",
                    verticalAlign: "middle",
                },
            },
            {
                name: "Variance",
                type: "line",
                symbol: "circle",
                symbolSize: 8,
                itemStyle: { color: "#f59e0b" },
                lineStyle: { width: 2, type: "dashed" },
                data: varianceData,
                label: {
                    show: true,
                    position: "bottom",
                    distance: 10, // Jarak dari titik biar angka minus ga nabrak garis putus-putus
                    formatter: (p) =>
                        (p.value > 0 ? "+" : "") + formatRibuan(p.value),
                    fontSize: 10,
                    fontWeight: "900",
                    color: "#92400e",
                },
            },
        ],
    };

    window.myResumeChart.setOption(option, true);
};

// ★ TOGGLE SIMILAR ROW — taruh di paling bawah file
window.toggleSimilarRow = function (rowId, badgeEl) {
    const row = document.getElementById("similar-" + rowId);
    if (!row) return;

    const isVisible = row.style.display !== "none";
    row.style.display = isVisible ? "none" : "table-row";

    if (badgeEl) {
        badgeEl.style.background = isVisible ? "#f59e0b" : "#10b981";
        badgeEl.style.color = isVisible ? "#000" : "#fff";
    }
};

// Fungsi untuk toggle (buka/tutup) semua row similar
function toggleAllSimilar() {
    // Cari semua elemen similar di dalam modal
    const similarRows = document.querySelectorAll(".similar-collapse-row");

    // Cek status baris pertama untuk menentukan apakah kita sedang "show" atau "hide"
    // Jika baris pertama display-nya none, berarti kita harus show semua
    const isHidden = similarRows[0].style.display === "none";

    similarRows.forEach((row) => {
        row.style.display = isHidden ? "table-row" : "none";
    });
}
