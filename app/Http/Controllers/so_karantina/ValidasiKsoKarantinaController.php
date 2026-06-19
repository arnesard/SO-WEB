<?php

namespace App\Http\Controllers\so_karantina;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB; // <-- Ganti use Model jadi use DB
use Illuminate\Http\Request;
use Carbon\Carbon;

class ValidasiKsoKarantinaController extends Controller
{
    public function index()
    {
        $team = session('validasi_team');
        $noDoc = session('validasi_no_doc');
        $item = null;
        $notFound = false;

        if ($team && $noDoc) {
            // <-- Pakai DB::table
            $item = DB::table('so_karantina_scan')
                ->where('NoDoc', $noDoc)
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->first();

            if (!$item) {
                $notFound = true;
            }
        }

        return view('so_karantina.4_validasi_kso_karantina', [
            'team' => $team,
            'noDoc' => $noDoc,
            'item' => $item,
            'notFound' => $notFound,
        ]);
    }

    public function setTeam(Request $request)
    {
        $request->validate([
            'team_hitung' => 'required|string|max:7',
        ]);

        session(['validasi_team' => $request->team_hitung]);
        session()->forget('validasi_no_doc');

        return redirect()->route('karantina.validasi.index');
    }

    public function setNoDoc(Request $request)
    {
        $request->validate([
            'no_doc' => 'required|string|max:7',
        ]);

        session(['validasi_no_doc' => $request->no_doc]);

        return redirect()->route('karantina.validasi.index');
    }

    public function resetTeam()
    {
        session()->forget(['validasi_team', 'validasi_no_doc']);
        return redirect()->route('karantina.validasi.index');
    }

    public function resetNoDoc()
    {
        session()->forget('validasi_no_doc');
        return redirect()->route('karantina.validasi.index');
    }

    public function approve(Request $request, $id)
    {
        // <-- Pakai DB::table
        DB::table('so_karantina_scan')
            ->where('id', $id)
            ->update([
                'status'     => 'approved',
                'opr_v'      => session('validasi_team'),
                'scantime_v' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        session()->forget('validasi_no_doc');

        return redirect()->route('karantina.validasi.index')->with('success', 'Data berhasil di-approve!');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'ket_reject' => 'required|string|max:150',
        ]);

        // <-- Pakai DB::table
        DB::table('so_karantina_scan')
            ->where('id', $id)
            ->update([
                'status'     => 'rejected',
                'ket_reject' => $request->ket_reject,
                'opr_v'      => session('validasi_team'),
                'scantime_v' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        session()->forget('validasi_no_doc');

        return redirect()->route('karantina.validasi.index')->with('success', 'Data berhasil di-reject!');
    }
}
