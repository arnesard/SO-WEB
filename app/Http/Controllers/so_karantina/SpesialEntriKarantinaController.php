<?php

namespace App\Http\Controllers\so_karantina;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB; // <-- Ganti use Model jadi use DB
use Illuminate\Http\Request;
use Carbon\Carbon;

class SpesialEntriKarantinaController extends Controller
{
    public function index()
    {
        return view('so_karantina.2_spesial_entry_karantina', [
            'team' => session('spesial_team'),
        ]);
    }

    public function setTeam(Request $request)
    {
        $request->validate([
            'team_hitung' => 'required|string|max:7',
        ]);

        session(['spesial_team' => $request->team_hitung]);

        return redirect()->route('karantina.spesial.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'team_hitung' => 'required|string|max:7',
            'no_doc'      => 'required|string|max:7',
            'item_code'   => 'required|string|max:100',
            'qty_stock'   => 'required|integer|min:0',
        ]);

        // Pengecekan apakah No. Doc sudah ada di database
        $cekNoDoc = DB::table('so_karantina_scan')
            ->where('NoDoc', $request->no_doc)
            ->exists();

        // Jika sudah ada, tolak dan kembalikan pesan error
        if ($cekNoDoc) {
            return back()->with('error', 'Gagal! No. Doc "' . $request->no_doc . '" sudah terpakai.');
        }

        // Jika belum ada, proses simpan data
        DB::table('so_karantina_scan')->insert([
            'opr'            => $request->team_hitung,
            'NoDoc'          => $request->no_doc,
            'item_code_desc' => $request->item_code,
            'QtyStk'         => $request->qty_stock,
            'txndate'        => Carbon::now()->format('Y-m-d H:i:s'),
            'status'         => 'pending',
        ]);

        return back()->with('success', 'Data berhasil disimpan!');
    }

    public function resetTeam()
    {
        session()->forget('spesial_team');
        return redirect()->route('karantina.spesial.index');
    }
}
