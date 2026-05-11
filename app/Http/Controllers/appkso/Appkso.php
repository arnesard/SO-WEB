<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Appkso extends Controller
{
    public function index()
    {
        // TARIK DATA DARI DATABASE KEDUA
        $list_kso = DB::connection('mysql_second') // Pakai koneksi yang baru dibuat
            ->table('ms_kso')
            ->select('so_name', 'def_counter')
            ->orderBy('recid', 'desc')
            ->get();

        // Data statistik (tetap dummy atau ambil dari db utama jika perlu)
        $summary = [
            'total_barang' => 1250,
            'sudah_opname' => 850,
            'belum_opname' => 400,
            'selisih' => 12
        ];

        $recent_activities = [
            ['tanggal' => '2026-05-01', 'petugas' => 'Adi Saputra', 'area' => 'Gudang B-1', 'status' => 'Selesai'],
            ['tanggal' => '2026-05-02', 'petugas' => 'Budi', 'area' => 'Gudang B-2', 'status' => 'Proses'],
            ['tanggal' => '2026-05-03', 'petugas' => 'Candra', 'area' => 'Gudang B-3', 'status' => 'Belum Mulai'],
        ];

        return view('appkso.appkso', compact('summary', 'recent_activities', 'list_kso'));
    }
}
