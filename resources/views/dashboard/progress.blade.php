<style>
    /* ===============================
        GLOBAL FUTURISTIC STYLE
    =============================== */

    .glass {
        background: rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        box-shadow: 0 0 20px rgba(0, 255, 255, 0.05);
    }

    .title-glow {
        text-shadow: 0 0 15px rgba(0, 255, 255, 0.6);
        letter-spacing: 2px;
    }

    .badge {
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 8px;
    }

    .table-dark {
        --bs-table-bg: transparent;
    }

    table {
        color: #fff;
    }

    thead th {
        background: rgba(0, 255, 255, 0.08) !important;
        color: #00f6ff;
        border-color: rgba(0, 255, 255, 0.1) !important;
        text-transform: uppercase;
        font-size: 11px;
    }

    tbody tr {
        transition: 0.2s;
    }

    tbody tr:hover {
        background: rgba(0, 255, 255, 0.05);
        transform: scale(1.01);
    }

    .progress-bar-glow {
        height: 6px;
        border-radius: 10px;
        background: linear-gradient(90deg, #00f6ff, #007bff, #00ff99);
        box-shadow: 0 0 10px rgba(0, 255, 255, 0.4);
    }

    .status-live {
        font-weight: bold;
        font-size: 11px;
        letter-spacing: 1px;
    }

    .status-ok {
        color: #00ff99;
    }

    .status-progress {
        color: #ffaa00;
    }
</style>


<body>

    <div class="container-fluid p-3">

        <!-- HEADER -->
        <div class="glass p-3 text-center mb-3">
            <h4 class="title-glow mb-0">
                Welcome Stock Opname MC Warehouse
            </h4>
        </div>

        <div class="row g-3">

            <!-- LEFT TABLE -->
            <div class="col-lg-8">

                <div class="glass p-2" style="height:82vh; overflow:auto;">

                    <table class="table table-borderless align-middle">

                        <thead>
                            <tr>

                                <th>No</th>
                                <th>Auditor</th>
                                <th>Auditee</th>
                                <th>Lokasi</th>

                                <th>KSO Total</th>
                                <th>KSO Verify</th>

                                <th>Product Total</th>
                                <th>Product Verify</th>

                                <th>%</th>
                                <th>Status</th>

                            </tr>
                        </thead>

                        <tbody>

                            @php $no = 1; @endphp

                            @foreach ($auditorsData as $row)
                                @php
                                    $isDone = $row['progres'] >= 100;
                                @endphp

                                <tr>

                                    <td>{{ $no++ }}</td>

                                    <td>
                                        {{ $row['auditor'] }}
                                    </td>

                                    <td>
                                        {{ $row['auditee'] }}
                                    </td>
                                    <td>
                                        {{ $row['lokasi'] }}
                                    </td>

                                    <!-- KSO -->
                                    <td class="text-center">
                                        {{ number_format($row['kso_total']) }}
                                    </td>

                                    <td class="text-center">
                                        {{ number_format($row['kso_verifikasi']) }}
                                    </td>

                                    <!-- PRODUCT -->
                                    <td class="text-end">
                                        {{ number_format($row['total_product']) }}
                                    </td>

                                    <td class="text-end">
                                        {{ number_format($row['verified_product']) }}
                                    </td>

                                    <!-- PROGRESS -->
                                    <td style="min-width:140px;">

                                        <div class="progress mb-1" style="height:6px; background:#111;">

                                            <div class="progress-bar-glow"
                                                style="width: {{ min(100, $row['progres']) }}%;">
                                            </div>

                                        </div>

                                        <small class="text-info">
                                            {{ number_format($row['progres'], 2) }}%
                                        </small>

                                    </td>

                                    <!-- STATUS -->
                                    <td>

                                        <span class="{{ $isDone ? 'status-ok' : 'status-progress' }}">

                                            {{ $isDone ? '✔ Completed' : '⏳ On Progress' }}

                                        </span>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>
            </div>

        </div>

    </div>
    </div>
</body>
<!-- AUTO REFRESH -->
<script>
    setTimeout(() => {
        location.reload();
    }, 30000);
</script>
