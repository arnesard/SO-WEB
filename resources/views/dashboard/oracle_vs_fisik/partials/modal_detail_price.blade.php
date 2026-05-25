{{-- resources\views\dashboard\oracle_vs_fisik\partials\modal_detail_price.blade.php --}}

<div class="row g-3">
    {{-- DATA MINUS (-) --}}
    <div class="col-12 col-xl-6">
        <div class="card border-danger mb-4 shadow-sm h-100">
            <div class="card-header bg-danger-subtle d-flex justify-content-between align-items-center w-100">
                <span class="text-dark fw-semibold">DATA MINUS (-) | {{ $minusData->count() }} SKU</span>
                <span class="fw-bold px-2 py-1 rounded bg-danger text-white" style="font-size: 13px;">
                    TOTAL VAR: {{ number_format($minusData->sum('variance'), 0, ',', '.') }} PCS | Rp
                    {{ number_format($minusData->sum('variance_rp'), 0, ',', '.') }}
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 80vh;">
                    <table class="table table-hover table-bordered table-sm mb-0 align-middle text-nowrap"
                        id="tableMinusPriceExport" style="font-size: 11px;">
                        <thead class="bg-light text-dark text-center align-middle sticky-top">
                            <tr>
                                <th rowspan="2" style="width: 3%;">NO</th>
                                <th rowspan="2">ITEM CODE</th>
                                <th rowspan="2" class="text-start">DESCRIPTION</th>
                                <th rowspan="2" class="text-end text-rose" style="color: #f43f5e;">Price (Rp)</th>
                                <th colspan="2" class="bg-primary-subtle">COUNTED</th>
                                <th colspan="2" class="bg-secondary-subtle">SNAPSHOT</th>
                                <th colspan="2" class="bg-danger-subtle">VARIANCE</th>
                            </tr>
                            <tr>
                                <th class="bg-primary-subtle text-end">Pcs</th>
                                <th class="bg-primary-subtle text-end">Rp</th>
                                <th class="bg-secondary-subtle text-end">Pcs</th>
                                <th class="bg-secondary-subtle text-end">Rp</th>
                                <th class="bg-danger-subtle text-end">Pcs</th>
                                <th class="bg-danger-subtle text-end">Rp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($minusData as $index => $row)
                                <tr class="clickable-item" data-item="{{ $row->item }}" style="cursor: pointer;"
                                    title="Klik untuk lihat riwayat scan">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-primary">{{ $row->item }}</td>
                                    <td class="text-truncate text-start" style="max-width: 200px;">
                                        {{ $row->description }}</td>

                                    {{-- Warning icon kalau harganya 0 --}}
                                    <td class="text-end fw-bold">
                                        @if ($row->price == 0)
                                            <i data-lucide="alert-triangle" class="text-danger me-1"
                                                style="width: 12px; height: 12px;"></i>
                                        @endif
                                        {{ number_format($row->price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">{{ number_format($row->appkso_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->counted_rp, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->oracle_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->snapshot_rp, 0, ',', '.') }}</td>

                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($row->variance, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($row->variance_rp, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-success fw-bold">Tidak ada data
                                        minus. Aman!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- DATA PLUS (+) --}}
    <div class="col-12 col-xl-6">
        <div class="card border-primary shadow-sm h-100">
            <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center w-100">
                <span class="text-dark fw-semibold">DATA PLUS (+) | {{ $plusData->count() }} SKU</span>
                <span class="fw-bold px-2 py-1 rounded bg-primary text-white" style="font-size: 13px;">
                    TOTAL VAR: +{{ number_format($plusData->sum('variance'), 0, ',', '.') }} PCS | Rp
                    +{{ number_format($plusData->sum('variance_rp'), 0, ',', '.') }}
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 80vh;">
                    <table class="table table-hover table-bordered table-sm mb-0 align-middle text-nowrap"
                        id="tablePlusPriceExport" style="font-size: 11px;">
                        <thead class="bg-light text-dark text-center align-middle sticky-top">
                            <tr>
                                <th rowspan="2" style="width: 3%;">NO</th>
                                <th rowspan="2">ITEM CODE</th>
                                <th rowspan="2" class="text-start">DESCRIPTION</th>
                                <th rowspan="2" class="text-end text-rose" style="color: #f43f5e;">Price (Rp)</th>
                                <th colspan="2" class="bg-primary-subtle">COUNTED</th>
                                <th colspan="2" class="bg-secondary-subtle">SNAPSHOT</th>
                                <th colspan="2" class="bg-info-subtle">VARIANCE</th>
                            </tr>
                            <tr>
                                <th class="bg-primary-subtle text-end">Pcs</th>
                                <th class="bg-primary-subtle text-end">Rp</th>
                                <th class="bg-secondary-subtle text-end">Pcs</th>
                                <th class="bg-secondary-subtle text-end">Rp</th>
                                <th class="bg-info-subtle text-end text-dark">Pcs</th>
                                <th class="bg-info-subtle text-end text-dark">Rp</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plusData as $index => $row)
                                <tr class="clickable-item" data-item="{{ $row->item }}" style="cursor: pointer;"
                                    title="Klik untuk lihat riwayat scan">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-primary">{{ $row->item }}</td>
                                    <td class="text-truncate text-start" style="max-width: 200px;">
                                        {{ $row->description }}</td>

                                    <td class="text-end fw-bold">
                                        @if ($row->price == 0)
                                            <i data-lucide="alert-triangle" class="text-danger me-1"
                                                style="width: 12px; height: 12px;"></i>
                                        @endif
                                        {{ number_format($row->price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end">{{ number_format($row->appkso_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->counted_rp, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->oracle_qty, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($row->snapshot_rp, 0, ',', '.') }}</td>

                                    <td class="text-end fw-bold text-primary">
                                        +{{ number_format($row->variance, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-primary">
                                        +{{ number_format($row->variance_rp, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-success fw-bold">Tidak ada data
                                        plus. Aman!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
