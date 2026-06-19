<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Revisi KSO</title>
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
            font-size: 18px;
        }

        .content {
            padding: 15px 10px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        input[type=text],
        input[type=number] {
            border: 1px solid #999;
            padding: 4px 6px;
            width: 220px;
            margin-top: 3px;
        }

        input[type=text]:focus,
        input[type=number]:focus {
            border-color: #2563eb;
            outline: none;
        }

        button {
            margin-top: 12px;
            padding: 5px 14px;
            background: #eee;
            border: 1px solid #999;
            cursor: pointer;
        }

        button:hover {
            background: #ddd;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-btn {
            margin-top: 20px;
        }

        .alert {
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 3px;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        .info-line {
            font-weight: bold;
            margin-bottom: 2px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>MENU REVISI KSO</h2>
    </div>
    <div class="content">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        <div id="alertBox" class="alert alert-error" style="display:none;"></div>

        @if (!$team)
            {{-- STEP 1: Input Team Hitung + Kode Dokumen --}}
            <form action="{{ route('karantina.revisi.setFilter') }}" method="POST">
                @csrf
                <label>TEAM HITUNG</label>
                <input type="text" name="team_hitung" autofocus required>

                <label>KODE DOKUMEN</label>
                <input type="text" name="kode_dokumen" placeholder="contoh: A01A001" required>

                <br>
                <button type="submit">OK</button>
            </form>
            <div class="back-btn">
                <a href="{{ route('so_karantina.menu') }}"><button type="button">&lt;-</button></a>
            </div>
        @else
            {{-- STEP 2: Revisi manual --}}
            <div class="info-line">TEAM HITUNG : {{ $team }}</div>
            <div class="info-line">KODE DOKUMEN : {{ $kodeDokumen }}</div>

            <form action="{{ route('karantina.revisi.update') }}" method="POST">
                @csrf
                <input type="hidden" name="team_hitung" value="{{ $team }}">
                <input type="hidden" name="kode_dokumen" value="{{ $kodeDokumen }}">

                <label>No. Doc:</label>
                <input type="text" name="no_doc" autofocus required>

                <label>Item Code:</label>
                <input type="text" name="item_code" required>

                <label>Qty Stock:</label>
                <input type="number" name="qty_stock" min="0" required>

                <br>
                <button type="submit">OK</button>
            </form>

            <div class="back-btn">
                <form action="{{ route('karantina.revisi.resetTeam') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">&lt;-</button>
                </form>
            </div>
        @endif

    </div>
</body>

</html>
