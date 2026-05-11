@extends('layouts.app')

@section('content')
    <!-- Header Card -->
    <div class="card border-0 shadow-sm mb-1" style="border-radius: 12px; border-left: 1px solid #0d6efd;">
        <div class="card-body p-2 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-dark">APPKSO</h4>
                <div class="d-flex align-items-center gap-2">
                    <p class="text-muted small mb-0">
                        Aplikasi Pelaksanaan Stock Opname •
                        <span class="fw-semibold text-primary">Gudang Ban B</span>
                    </p>
                </div>
            </div>
            <div style="min-width: 300px;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="small fw-bold text-muted mb-0">
                        <i data-lucide="filter" size="14"></i> Pilih Project SO
                    </label>
                    <!-- Tempat nampilin def_counter (Default ambil dari data pertama/terbaru) -->
                    <span id="display_counter" class="badge bg-info-subtle text-info border border-info-subtle small">
                        Counter: {{ $list_kso->first()->def_counter ?? '-' }}
                    </span>
                </div>

                <select id="so_select" class="form-select border-primary-subtle shadow-sm" style="border-radius: 8px;">
                    <option value="" data-counter="-">-- Pilih SO Name --</option>
                    @foreach ($list_kso as $kso)
                        <option value="{{ $kso->so_name }}" data-counter="{{ $kso->def_counter }}">
                            {{ $kso->so_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Statistik Row -->
    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['label' => 'Total Item', 'value' => $summary['total_barang'], 'color' => 'primary'],
                ['label' => 'Sudah Dicek', 'value' => $summary['sudah_opname'], 'color' => 'success'],
                ['label' => 'Belum Dicek', 'value' => $summary['belum_opname'], 'color' => 'warning'],
                ['label' => 'Ada Selisih', 'value' => $summary['selisih'], 'color' => 'danger'],
            ];
        @endphp
        @foreach ($stats as $stat)
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 text-center" style="border-radius: 12px;">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">{{ $stat['label'] }}</small>
                    <h2 class="fw-bold mb-0 text-{{ $stat['color'] }}">{{ $stat['value'] }}</h2>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <!-- Aksi Cepat -->
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Aksi Cepat</h6>
                    <div class="d-grid gap-2">
                        <button
                            class="btn btn-primary py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i data-lucide="play-circle" size="18"></i> Mulai Opname
                        </button>
                        <button class="btn btn-outline-dark py-2 d-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="file-text" size="18"></i> Laporan KSO
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Utama -->
        <div class="col-md-9">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Aktivitas Opname Terakhir</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="border-0 ps-3">Tanggal</th>
                                <th class="border-0">Petugas</th>
                                <th class="border-0">Area</th>
                                <th class="border-0">Status</th>
                                <th class="border-0 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent_activities as $act)
                                <tr>
                                    <td class="ps-3">{{ $act['tanggal'] }}</td>
                                    <td class="fw-semibold">{{ $act['petugas'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $act['area'] }}</span></td>
                                    <td>
                                        @if ($act['status'] == 'Selesai')
                                            <span class="badge bg-success-subtle text-success">Selesai</span>
                                        @elseif($act['status'] == 'Proses')
                                            <span class="badge bg-primary-subtle text-primary">Proses</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Menunggu</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-white border px-3">Detail</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const soSelect = document.getElementById('so_select');
        const displayCounter = document.getElementById('display_counter');

        soSelect.addEventListener('change', function() {
            // Ambil data-counter dari attribute option yang dipilih
            const selectedOption = this.options[this.selectedIndex];
            const counter = selectedOption.getAttribute('data-counter');

            // Update tulisan di label
            displayCounter.innerHTML = 'Counter: ' + counter;
        });
    });
</script>
