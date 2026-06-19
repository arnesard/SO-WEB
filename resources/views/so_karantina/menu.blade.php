<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>SO Karantina</title>
    <style>
        body {
            background: #ff9900;
            font-family: 'Times New Roman', serif;
            margin: 0;
        }

        .header {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            padding: 8px 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .menu-box {
            padding: 10px;
            font-size: 16px;
        }

        .menu-box div {
            margin-bottom: 4px;
        }

        .pilihan-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-weight: bold;
        }

        .pilihan-row input {
            width: 30px;
            border: 1px solid #999;
            padding: 2px 4px;
            font-family: 'Times New Roman', serif;
            font-size: 15px;
        }

        .footer {
            border-top: 2px solid #333;
            font-size: 11px;
            padding: 5px 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>COUNT STOCK OPNAME</h2>
    </div>

    <div class="menu-box">
        {{-- <div>1. ENTRY KSO</div> --}}
        <div>1. SPESIAL ENTRY</div>
        <div>2. REVISI KSO</div>
        <div>3. VALIDASI KSO</div>
        <div class="pilihan-row">
            PILIHAN :
            <input type="text" id="inputPilihan" maxlength="1" autofocus>
        </div>

        {{-- <div style="margin-top: 10px;">
            <a href="{{ route('pilihmenu.index') }}"
                style="text-decoration: none; color: #333; font-weight: bold; border: 1px solid #333; padding: 4px 10px; border-radius: 4px; background: #ffd699;">
                ← KEMBALI KE MENU
            </a>
        </div> --}}
    </div>

    <div class="footer">
        PT Gajah Tunggal Tbk<br>
        <span id="jamRealtime"></span>
    </div>

    <script>
        // Jam realtime
        function updateJam() {
            const now = new Date();
            const dd = String(now.getDate()).padStart(2, '0');
            const bulan = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
            const mmm = bulan[now.getMonth()];
            const yyyy = now.getFullYear();
            const hh = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const ampm = now.getHours() < 12 ? 'AM' : 'PM';
            document.getElementById('jamRealtime').textContent =
                `${dd}-${mmm}-${yyyy} ${hh}:${min}:${ss} ${ampm}`;
        }
        updateJam();
        setInterval(updateJam, 1000);

        // Navigasi via input pilihan
        const routes = {
            // '1': "{{ route('karantina.spesial.index') }}",
            '1': "{{ route('karantina.spesial.index') }}", // ganti kalau ada route entry KSO
            '2': "{{ route('karantina.revisi.index') }}",
            '3': "{{ route('karantina.validasi.index') }}",
        };

        document.getElementById('inputPilihan').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const val = this.value.trim();
                if (routes[val]) {
                    window.location.href = routes[val];
                }
            }
        });
    </script>
</body>

</html>
