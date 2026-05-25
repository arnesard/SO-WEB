<div class="row g-3">
    {{-- DATA MINUS (-) --}}
    <div class="col-12 col-xl-6">
        <div class="card border-danger shadow-sm h-100">
            <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                <span>DATA MINUS (-) : {{ count($minusData) }} SKU</span>
                <span class="badge bg-white text-danger fw-black" style="font-size: 12px;">
                    TOTAL VAR: {{ number_format($minusData->sum('variance'), 0, ',', '.') }} PCS
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 80vh;">
                    <table class="table table-sm table-hover table-bordered mb-0 align-middle text-nowrap"
                        style="font-size: 11px;" id="tableMinusGradeExport">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 5%;">NO</th>
                                <th>PATTERN</th>
                                <th>ITEM CODE</th>
                                <th class="text-start">DESCRIPTION</th>
                                <th class="text-end">COUNTED</th>
                                <th class="text-end">SNAPSHOT</th>
                                <th class="text-end">VAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($minusData as $index => $row)
                                <tr class="clickable-item" data-item="{{ $row->item }}" style="cursor: pointer;"
                                    title="Klik untuk lihat riwayat scan">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ $row->pattern }}</td>
                                    <td class="fw-bold text-primary">{{ $row->item }}</td>
                                    <td class="text-truncate text-start" style="max-width: 150px;">
                                        {{ $row->description }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->appkso_qty, 0, ',', '.') }}</td>
                                    <td class="text-end text-dark">{{ number_format($row->oracle_qty, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end text-danger fw-bold">
                                        {{ number_format($row->variance, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-success fw-bold py-4">Tidak ada data
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
            <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                <span>DATA PLUS (+) : {{ count($plusData) }} SKU</span>
                <span class="badge bg-white text-primary fw-black" style="font-size: 12px;">
                    TOTAL VAR: +{{ number_format($plusData->sum('variance'), 0, ',', '.') }} PCS
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 80vh;">
                    <table class="table table-sm table-hover table-bordered mb-0 align-middle text-nowrap"
                        style="font-size: 11px;" id="tablePlusGradeExport">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="text-center" style="width: 5%;">NO</th>
                                <th>PATTERN</th>
                                <th>ITEM CODE</th>
                                <th class="text-start">DESCRIPTION</th>
                                <th class="text-end">COUNTED</th>
                                <th class="text-end">SNAPSHOT</th>
                                <th class="text-end">VAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plusData as $index => $row)
                                <tr class="clickable-item" data-item="{{ $row->item }}" style="cursor: pointer;"
                                    title="Klik untuk lihat riwayat scan">
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ $row->pattern }}</td>
                                    <td class="fw-bold text-primary">{{ $row->item }}</td>
                                    <td class="text-truncate text-start" style="max-width: 150px;">
                                        {{ $row->description }}</td>
                                    <td class="text-end fw-bold">{{ number_format($row->appkso_qty, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end text-dark">{{ number_format($row->oracle_qty, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        +{{ number_format($row->variance, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data plus. Aman!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
