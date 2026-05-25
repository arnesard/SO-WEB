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
     * Menyimpan atau memperbarui data personil area (Role-Based Route) + Anti Overlap LOT
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

        // Bersihkan data dari spasi berlebih
        $warehouse = strtoupper(trim($request->warehouse));
        $gedung = strtoupper(trim($request->gedung));
        $inputLotRaw = strtoupper(str_replace(' ', '', $request->lot));

        // =========================================================================
        // 🛠️ FASE 1: VALIDASI FORMAT & LINTAS HURUF ABJAD
        // =========================================================================
        // Ngecek format harus HurufAngka-HurufAngka (Contoh: A01-A10)
        if (!preg_match('/^([A-Z]+)(\d+)-([A-Z]+)(\d+)$/', $inputLotRaw, $matches)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format LOT tidak valid bro! Gunakan format AbjadAngka-AbjadAngka, contoh: A01-A10.'
            ], 400);
        }

        $startPrefix = $matches[1]; // "A"
        $startNum = (int) $matches[2]; // "1" (Otomatis parse 01 jadi 1)
        $endPrefix = $matches[3]; // "A"
        $endNum = (int) $matches[4]; // "10"

        // Cek kalau huruf abjad awal dan akhir beda (Lintas huruf)
        if ($startPrefix !== $endPrefix) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal! Huruf awalan LOT harus sama (contoh yang benar: A01-A10). Lintas abjad seperti A01-B10 tidak diizinkan.'
            ], 400);
        }

        // Cek kalau input kebalik (misal A10-A01)
        if ($startNum > $endNum) {
            return response()->json([
                'status' => 'error',
                'message' => 'Logika kebalik bro! Angka awal LOT tidak boleh lebih besar dari angka akhir.'
            ], 400);
        }

        // =========================================================================
        // 🛠️ FASE 2: VALIDASI TUMPANG TINDIH (OVERLAP LOGIC)
        // =========================================================================
        // Tarik data yang ada di tabel, warehouse, dan gedung yang sama
        $query = DB::table($table)
            ->where('warehouse', $warehouse)
            ->where('gedung', $gedung);

        // Kalau mode Update (Edit), data dia sendiri jangan ikut di-cek bentrok
        if (!empty($id)) {
            $query->where('id', '!=', $id);
        }

        $existingData = $query->get(['nama', 'lot', 'no_penneng']);

        foreach ($existingData as $row) {
            $dbLot = strtoupper(str_replace(' ', '', $row->lot));

            // Parse data LOT lama yang ada di DB
            if (preg_match('/^([A-Z]+)(\d+)-([A-Z]+)(\d+)$/', $dbLot, $dbMatches)) {
                $dbStartPrefix = $dbMatches[1];
                $dbStartNum = (int) $dbMatches[2];
                $dbEndPrefix = $dbMatches[3]; // Sebenernya gak dipake karna udah divalidasi pas masuk
                $dbEndNum = (int) $dbMatches[4];

                // Cek tumpukan HANYA JIKA abjadnya sama (sama-sama A, atau sama-sama B)
                if ($startPrefix === $dbStartPrefix) {

                    // 🎯 CORE MATEMATIKA IRISAN RENTANG (OVERLAP LOGIC)
                    if (($startNum <= $dbEndNum) && ($endNum >= $dbStartNum)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Gagal Bro! Rencana LOT [{$inputLotRaw}] bertabrakan dengan rentang [{$row->lot}] yang sudah ditugaskan ke {$row->nama} ({$row->no_penneng}) di Gedung {$gedung}."
                        ], 400);
                    }
                }
            }
        }

        // =========================================================================
        // 🛠️ FASE 3: LOLOS UJI - SIMPAN KE DATABASE
        // =========================================================================
        $data = [
            'warehouse'  => $warehouse,
            'no_penneng' => strtoupper(trim($request->no_penneng)),
            'nama'       => trim($request->nama),
            'gedung'     => $gedung,
            'lot'        => $inputLotRaw, // Format rapi tanpa spasi
            'updated_at' => now()
        ];

        try {
            if (!empty($id)) {
                // Mode Update
                DB::table($table)->where('id', $id)->update($data);
                $msg = "Data personil " . $request->role_type . " berhasil diperbarui tanpa bentrok LOT!";
            } else {
                // Mode Simpan Baru
                $data['created_at'] = now();
                DB::table($table)->insert($data);
                $msg = "Personel baru " . $request->role_type . " sukses didaftarkan dan LOT aman!";
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
