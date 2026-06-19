<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Special Entry</title>
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

        .info-line {
            font-weight: bold;
            margin-bottom: 2px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>MENU SPECIAL ENTRY</h2>
    </div>
    <div class="content">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error" style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b;">
                {{ session('error') }}
            </div>
        @endif
        @if (!$team)
            {{-- STEP 1: Input Team Hitung --}}
            <form action="{{ route('karantina.spesial.setTeam') }}" method="POST">
                @csrf
                <label>TEAM HITUNG</label>
                <input type="text" name="team_hitung" autofocus required>
                <br>
                <button type="submit">OK</button>
            </form>
            <div class="back-btn">
                <a href="{{ route('so_karantina.menu') }}"><button type="button">&lt;-</button></a>
            </div>
        @else
            {{-- STEP 2: Form scan data --}}
            <div class="info-line">TEAM HITUNG : {{ $team }}</div>

            <form action="{{ route('karantina.spesial.store') }}" method="POST">
                @csrf
                <input type="hidden" name="team_hitung" value="{{ $team }}">

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
                <form action="{{ route('karantina.spesial.resetTeam') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">&lt;-</button>
                </form>
            </div>
        @endif

    </div>
</body>

</html>
