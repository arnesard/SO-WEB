<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikPicController extends Controller
{
    /**
     * Menarik seluruh data personil terdaftar dari kedua tabel
     */
    public function getData()
    {
        try {
            $stockTeam = DB::table('so_all_wh_pic_stock_db')->orderBy('id', 'desc')->get();
            $auditorTeam = DB::table('so_all_wh_pic_auditor_db')->orderBy('id', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'stock_team' => $stockTeam,
                'auditor_team' => $auditorTeam
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menyimpan atau memperbarui data personil area (Role-Based Route)
     */
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'role_type'   => 'required|in:STOCK,AUDITOR',
            'warehouse'   => 'required|string|max:50',
            'no_penneng'  => 'required|string|max:100',
            'nama'        => 'required|string|max:150',
            'gedung'      => 'required|string|max:100',
            'lot'         => 'required|string|max:100',
        ]);

        $table = ($request->role_type === 'STOCK') ? 'so_all_wh_pic_stock_db' : 'so_all_wh_pic_auditor_db';
        $id = $request->entry_id;

        $data = [
            'warehouse'  => strtoupper(trim($request->warehouse)),
            'no_penneng' => strtoupper(trim($request->no_penneng)),
            'nama'       => trim($request->nama),
            'gedung'     => strtoupper(trim($request->gedung)),
            'lot'        => strtoupper(trim($request->lot)),
            'updated_at' => now()
        ];

        try {
            if (!empty($id)) {
                // Mode Update
                DB::table($table)->where('id', $id)->update($data);
                $msg = "Data personil " . $request->role_type . " berhasil diperbarui bro!";
            } else {
                // Mode Simpan Baru
                $data['created_at'] = now();
                DB::table($table)->insert($data);
                $msg = "Personel baru " . $request->role_type . " sukses didaftarkan!";
            }

            return response()->json(['status' => 'success', 'message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal simpan SQL: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus data personil berdasarkan tipe tabel tujuan
     */
    public function destroy(Request $request, $id)
    {
        $role = $request->get('role');
        if (!in_array($role, ['STOCK', 'AUDITOR'])) {
            return response()->json(['status' => 'error', 'message' => 'Spesifikasi tim tidak valid!'], 400);
        }

        $table = ($role === 'STOCK') ? 'so_all_wh_pic_stock_db' : 'so_all_wh_pic_auditor_db';

        try {
            DB::table($table)->where('id', $id)->delete();
            return response()->json(['status' => 'success', 'message' => 'Personel lapangan resmi dihapus dari area!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal hapus SQL: ' . $e->getMessage()], 500);
        }
    }
}
