<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Web Stock Opname</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, sans-serif;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .nav-link {
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563 !important;
        }

        .nav-link:hover {
            color: #0d6efd !important;
        }

        .nav-link.active {
            color: #0d6efd !important;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top px-3">
        <div class="container-fluid"> <a class="navbar-brand d-flex align-items-center gap-2"
                href="{{ route('dashboard.index') }}" onclick="localStorage.clear();">
                <img src="{{ asset('images/logo-gt.png') }}" alt="Logo" style="height: 45px;">
                <div class="d-flex flex-column lh-1">
                    <span class="fw-bold text-dark" style="font-size: 1rem;">PT. Gajah Tunggal Tbk.,</span>
                    <span class="text-muted" style="font-size: 0.85rem;">Gudang Ban B Dept.</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @if (!request()->is('appkso*'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active fw-bold' : '' }}"
                                href="{{ route('dashboard.index') }}" onclick="localStorage.clear();">
                                <i data-lucide="layout-dashboard" size="18"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('data_master/report-daily') ? 'active fw-bold' : '' }}"
                                href="{{ route('data_master.report') }}">
                                <i data-lucide="package" size="18"></i> Data Master BPW
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('so-karantina*') ? 'active fw-bold' : '' }}"
                                href="{{ route('so_karantina.index') }}">
                                <i data-lucide="file" size="18"></i> SO Karantina
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('appkso') ? 'active' : '' }}"
                            href="{{ route('appkso.index') }}" id="btn-auto-so">
                            <i data-lucide="clipboard-check" size="18"></i> Auto SO BPW
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-2 px-4">
        @yield('content')
    </div>

    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/excel.min.js') }}"></script>
    <script src="{{ asset('js/FileSaver.js') }}"></script>
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/echarts.min.js') }}"></script>
    <script src="{{ asset('js/xlsx.full.min.js') }}"></script>


    @stack('scripts')
    <script>
        lucide.createIcons();

        document.getElementById('btn-auto-so').addEventListener('click', function(e) {
            e.preventDefault(); // Mencegah link langsung kebuka

            // Ambil URL tujuan dari atribut href
            const targetUrl = this.getAttribute('href');

            // Tentukan password yang dimau
            const correctPassword = "naura";

            Swal.fire({
                title: 'Akses Dibatasi',
                text: 'Masukkan password untuk membuka menu Auto SO BPW',
                input: 'text', // Kita tipu browser pakai tipe text
                inputPlaceholder: 'Ketik password di sini...',
                inputAttributes: {
                    // Trik CSS untuk menyensor teks yang diketik biar jadi titik-titik
                    style: '-webkit-text-security: disc;'
                },
                type: 'warning', // <-- UBAH 'icon' JADI 'type'
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Masuk',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Cek apakah user menekan tombol 'Masuk' (result memiliki value)
                if ('value' in result) {
                    // Cek apakah password cocok
                    if (result.value === correctPassword) {
                        // JIKA BENAR: Munculkan notifikasi sukses
                        Swal.fire({
                            type: 'success', // <-- UBAH 'icon' JADI 'type'
                            title: 'Akses Diberikan!',
                            text: 'Mengalihkan ke halaman...',
                            timer: 1500, // Tunggu 1.5 detik
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = targetUrl; // Eksekusi pindah halaman
                        });
                    } else {
                        // JIKA SALAH: Munculkan error
                        Swal.fire({
                            type: 'error', // <-- UBAH 'icon' JADI 'type'
                            title: 'Akses Ditolak!',
                            text: 'Password yang Anda masukkan salah.'
                        });
                    }
                }
            });
        });
    </script>
</body>

</html>
