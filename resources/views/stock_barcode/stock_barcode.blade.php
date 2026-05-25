@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
        {{-- Header --}}
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; border-left: 6px solid #fe6807;">
            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-black mb-0 text-dark" style="letter-spacing: -1px;">STOCK BARCODE ENGINE</h4>
                    <p class="text-muted small mb-0"><i data-lucide="server" class="me-1" size="12"></i> Data Processor:
                        <span class="fw-bold text-orange">.126 &rightarrow; .179</span>
                    </p>
                </div>
                <button class="btn fw-bold text-white shadow-sm" id="btnStartSync"
                    style="background-color: #fe6807; border-radius: 10px;">
                    <i data-lucide="refresh-cw" class="me-2" size="16"></i> JALANKAN SYNC ULANG
                </button>
            </div>
        </div>

        {{-- Stats Row --}}
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4"
                    style="border-radius: 20px; background: linear-gradient(135deg, #fe6807, #ff8c42);">
                    <div class="d-flex justify-content-between align-items-start text-white">
                        <div>
                            <small class="fw-bold opacity-75 d-block mb-1 text-uppercase">Total Quantity</small>
                            <h1 class="fw-black mb-0">{{ number_format($summary->total_qty ?? 0) }}</h1>
                        </div>
                        <i data-lucide="package" size="40" class="opacity-25"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4"
                    style="border-radius: 20px; background: linear-gradient(135deg, #1e293b, #334155);">
                    <div class="d-flex justify-content-between align-items-start text-white">
                        <div>
                            <small class="fw-bold opacity-75 d-block mb-1 text-uppercase">Unique SKU</small>
                            <h1 class="fw-black mb-0">{{ number_format($summary->total_sku ?? 0) }}</h1>
                        </div>
                        <i data-lucide="layers" size="40" class="opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Information Area --}}
        <div class="mt-4 p-5 border-2 border-dashed rounded-4 text-center bg-white shadow-sm"
            style="border-color: #dee2e6 !important;">
            <div id="statusIcon">
                <i data-lucide="database" class="text-muted mb-3" size="64"></i>
            </div>
            <h5 class="fw-bold text-dark">Data Reconciliation System</h5>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                Sistem akan menghapus data lokal dan menarik ulang hasil kalkulasi terbaru dari database produksi untuk
                menjamin akurasi stok rack berawalan <b class="text-orange">B</b> dan <b class="text-orange">~</b>.
            </p>
        </div>
    </div>

    {{-- SweetAlert2 & Logic --}}
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script>
        document.getElementById('btnStartSync').addEventListener('click', function() {
            // Cek nama fungsi globalnya (biasanya Swal atau Sweetalert2)
            const MySwal = (typeof Swal !== 'undefined') ? Swal : (typeof Sweetalert2 !== 'undefined' ?
                Sweetalert2 : null);

            if (!MySwal) {
                alert('Library SweetAlert2 tidak ditemukan di folder public/js/! Cek file-nya bro.');
                return;
            }

            MySwal.fire({
                title: 'Konfirmasi Sync Ulang?',
                text: "Estimasi 2-5 menit.",
                type: 'warning', // Pakai 'type' bukan 'icon' untuk versi lama
                showCancelButton: true,
                confirmButtonColor: '#fe6807',
                confirmButtonText: 'Ya, Sikat Bro!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Cek result.value karena versi lama sering pake itu
                if (result.value || result.isConfirmed) {
                    let timerInterval;
                    let progress = 0;

                    MySwal.fire({
                        title: 'Sedang Berhitung...',
                        // Gunakan HTML mentah
                        html: `
                        <div style="margin-top: 15px; background-color: #eee; border-radius: 10px; overflow: hidden; height: 25px; width: 100%; border: 1px solid #ccc;">
                            <div id="sync-progress" style="background-color: #fe6807; height: 100%; width: 0%; color: white; font-weight: bold; line-height: 25px; text-align: center; transition: width 0.4s;">0%</div>
                        </div>
                        <p style="margin-top: 10px; font-size: 12px; color: #666;">Sedang Mengambil database Server <br> Jangan di-refresh ya bro!</p>
                    `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        onBeforeOpen: () => { // Versi lama pake onBeforeOpen atau didOpen
                            MySwal.showLoading();
                            const progressBar = document.getElementById('sync-progress');
                            timerInterval = setInterval(() => {
                                if (progress < 95) {
                                    progress += 0.5;
                                    if (progressBar) {
                                        progressBar.style.width = progress + '%';
                                        progressBar.textContent = Math.round(progress) +
                                            '%';
                                    }
                                }
                            }, 1000);
                        }
                    });

                    // Eksekusi AJAX ke Controller
                    fetch("{{ route('stock.barcode.sync') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            clearInterval(timerInterval);
                            if (data.status === 'success') {
                                MySwal.fire({
                                    title: 'Berhasil!',
                                    text: data.message,
                                    type: 'success',
                                    confirmButtonColor: '#fe6807'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                MySwal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            clearInterval(timerInterval);
                            MySwal.fire({
                                title: 'Proses Background',
                                text: 'Browser timeout, tapi tenang! Server Database tetap lanjut berhitung. Tunggu 5 menit lalu refresh manual ya.',
                                type: 'info',
                                confirmButtonColor: '#fe6807'
                            });
                        });
                }
            });
        });
    </script>
@endsection
