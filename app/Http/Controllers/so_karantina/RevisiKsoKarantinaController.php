<?php

namespace App\Http\Controllers\so_karantina;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RevisiKsoKarantinaController extends Controller
{
    public function index()
    {
        return view('so_karantina.3_revisi_kso_karantina', [
            'team' => session('revisi_team'),
            'kodeDokumen' => session('revisi_kode_dokumen'),
        ]);
    }

    public function setFilter(Request $request)
    {
        $request->validate([
            'team_hitung'  => 'required|string|max:7',
            'kode_dokumen' => 'required|string|max:7',
        ]);

        // Cek apakah kombinasi Team (opr) dan Kode Dokumen (NoDoc) ada di database
        $isValid = DB::table('so_karantina_scan')
            ->where('opr', $request->team_hitung)
            ->where('NoDoc', $request->kode_dokumen)
            ->exists();

        if (!$isValid) {
            return back()->with('error', 'Gagal! Kombinasi Team Hitung dan Kode Dokumen tidak ditemukan di database.');
        }

        session([
            'revisi_team' => $request->team_hitung,
            'revisi_kode_dokumen' => $request->kode_dokumen,
        ]);

        return redirect()->route('karantina.revisi.index');
    }

    public function resetTeam()
    {
        session()->forget(['revisi_team', 'revisi_kode_dokumen']);
        return redirect()->route('karantina.revisi.index');
    }

    public function update(Request $request)
    {
        $request->validate([
            'no_doc'    => 'required|string|max:50',
            'item_code' => 'required|string|max:100',
            'qty_stock' => 'required|integer|min:0',
        ]);

        // <-- Update DB::table (Cocokkan juga dengan opr dari session)
        $updated = DB::table('so_karantina_scan')
            ->where('NoDoc', $request->no_doc)
            ->where('opr', session('revisi_team')) // Tambahan pengaman
            ->update([
                'item_code_desc' => $request->item_code,
                'QtyStk'         => $request->qty_stock,
            ]);

        // Jika tidak ada data yang terupdate (No Doc tidak ketemu)
        if ($updated === 0) {
            return back()->with('error', 'Data gagal direvisi, No. Doc tidak ditemukan untuk Team ini!');
        }

        return back()->with('success', 'Data berhasil direvisi!');
    }
}
