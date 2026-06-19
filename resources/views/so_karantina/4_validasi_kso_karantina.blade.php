<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Validasi KSO</title>
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

        input[type=text] {
            border: 1px solid #999;
            padding: 4px 6px;
            width: 220px;
            margin-top: 3px;
        }

        input[type=text]:focus {
            border-color: #2563eb;
            outline: none;
        }

        textarea {
            border: 1px solid #999;
            padding: 4px 6px;
            width: 320px;
            margin-top: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        textarea:focus {
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

        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }

        .info-line {
            font-weight: bold;
            margin-bottom: 2px;
        }

        table {
            border-collapse: collapse;
            margin: 10px 0;
            min-width: 360px;
        }

        table td,
        table th {
            border: 1px solid #333;
            padding: 5px 8px;
            text-align: left;
        }

        .action-row {
            display: flex;
            gap: 8px;
        }

        .approve-btn {
            background: #f0fff4;
            border-color: #2e9e44;
            color: #166534;
        }

        .reject-btn {
            background: #fff0f0;
            border-color: #d33;
            color: #b91c1c;
        }

        .reject-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid #ccc;
            max-width: 360px;
        }

        .reject-form.show {
            display: block;
        }

        .reject-submit-btn {
            background: #ffe4e4;
            border-color: #b91c1c;
            color: #7f1d1d;
        }

        .empty-msg {
            font-style: italic;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>VALIDASI KSO</h2>
    </div>
    <div class="content">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (!$team)
            {{-- STEP 1: Input Team Hitung --}}
            <form action="{{ route('karantina.validasi.setTeam') }}" method="POST">
                @csrf
                <label>TEAM HITUNG</label>
                <input type="text" name="team_hitung" autofocus required>
                <br>
                <button type="submit">OK</button>
            </form>
            <div class="back-btn">
                <a href="{{ route('so_karantina.menu') }}"><button type="button">&lt;-</button></a>
            </div>
        @elseif(!$noDoc)
            {{-- STEP 2: Input No.Doc --}}
            <div class="info-line">TEAM HITUNG : {{ $team }}</div>

            <form action="{{ route('karantina.validasi.setNoDoc') }}" method="POST">
                @csrf
                <label>No. Doc:</label>
                <input type="text" name="no_doc" autofocus required>
                <br>
                <button type="submit">OK</button>
            </form>

            <div class="back-btn">
                <form action="{{ route('karantina.validasi.resetTeam') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">&lt;-</button>
                </form>
            </div>
        @else
            {{-- STEP 3: Tampil detail data + Approve/Reject --}}
            <div class="info-line">TEAM HITUNG : {{ $team }}</div>

            @if ($notFound)
                <div class="alert alert-error">Data dengan No.Doc "{{ $noDoc }}" tidak ditemukan atau sudah
                    divalidasi.</div>
            @else
                <table>
                    <tr>
                        <td><strong>No Doc</strong></td>
                        <td>{{ $item->NoDoc }}</td>
                    </tr>
                    <tr>
                        <td><strong>Item</strong></td>
                        <td>{{ $item->item_code_desc }}</td>
                    </tr>
                    <tr>
                        <td><strong>Qty</strong></td>
                        <td>{{ $item->QtyStk }}</td>
                    </tr>
                </table>

                <div class="action-row">
                    <form action="{{ route('karantina.validasi.approve', $item->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="approve-btn">APPROVE</button>
                    </form>

                    {{-- <button type="button" class="reject-btn" onclick="toggleRejectForm()">REJECT</button> --}}
                </div>

                {{-- <div class="reject-form" id="rejectForm">
                    <form action="{{ route('karantina.validasi.reject', $item->id) }}" method="POST">
                        @csrf
                        <label>Keterangan Reject:</label>
                        <textarea name="ket_reject" rows="2" maxlength="150" required placeholder="Tulis alasan reject..."></textarea>
                        <br>
                        <button type="submit" class="reject-submit-btn">SUBMIT REJECT</button>
                    </form>
                </div> --}}
            @endif

            <div class="back-btn">
                <form action="{{ route('karantina.validasi.resetNoDoc') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit">&lt;-</button>
                </form>
            </div>

            <script>
                function toggleRejectForm() {
                    document.getElementById('rejectForm').classList.toggle('show');
                }
            </script>
        @endif

    </div>
</body>

</html>
