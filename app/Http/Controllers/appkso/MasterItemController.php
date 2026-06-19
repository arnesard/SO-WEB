<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterItemController extends Controller
{
    public function index()
    {
        $currentRoute = request()->route()->getName();
        return view('appkso.master_item', compact('currentRoute'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->except(['_token', 'id']);
            DB::table('master_items')->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->except(['_token', 'id']);
            DB::table('master_items')->where('id', $id)->update(array_merge($data, [
                'updated_at' => now()
            ]));
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::table('master_items')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function getData()
    {
        $data = DB::table('master_items')->orderBy('id', 'desc')->get();
        return response()->json($data);
    }

    // =========================================================
    // MASTER ITEM SIMILAR (NEW)
    // =========================================================

    /**
     * Ambil semua data similar, join ke master_items untuk dapat deskripsi,
     * join ke snapshot_bpw & cntso untuk info stok terkait.
     */
    public function getSimilarData()
    {
        try {
            $data = DB::table('master_item_similar')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Simpan relasi similar baru.
     * Validasi: duplikat pair (A-B) dan kebalikannnya (B-A) tidak boleh ada.
     */
    public function storeSimilar(Request $request)
    {
        try {
            $itemCode        = strtoupper(trim($request->ItemCode));
            $itemCodeSimilar = strtoupper(trim($request->ItemCodeSimilar));

            if ($itemCode === $itemCodeSimilar) {
                return response()->json(['success' => false, 'message' => 'ItemCode dan ItemCodeSimilar tidak boleh sama!']);
            }

            // Cek duplikat dua arah
            $exists = DB::table('master_item_similar')
                ->where(function ($q) use ($itemCode, $itemCodeSimilar) {
                    $q->where('ItemCode', $itemCode)->where('ItemCodeSimilar', $itemCodeSimilar);
                })
                ->orWhere(function ($q) use ($itemCode, $itemCodeSimilar) {
                    $q->where('ItemCode', $itemCodeSimilar)->where('ItemCodeSimilar', $itemCode);
                })
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Pasangan item similar ini sudah terdaftar!']);
            }

            DB::table('master_item_similar')->insert([
                'ItemCode'        => $itemCode,
                'ItemCodeSimilar' => $itemCodeSimilar,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update relasi similar yang sudah ada.
     */
    public function updateSimilar(Request $request, $id)
    {
        try {
            $itemCode        = strtoupper(trim($request->ItemCode));
            $itemCodeSimilar = strtoupper(trim($request->ItemCodeSimilar));

            if ($itemCode === $itemCodeSimilar) {
                return response()->json(['success' => false, 'message' => 'ItemCode dan ItemCodeSimilar tidak boleh sama!']);
            }

            // Cek duplikat dua arah (kecuali record sendiri)
            $exists = DB::table('master_item_similar')
                ->where('id', '!=', $id)
                ->where(function ($q) use ($itemCode, $itemCodeSimilar) {
                    $q->where(function ($q2) use ($itemCode, $itemCodeSimilar) {
                        $q2->where('ItemCode', $itemCode)->where('ItemCodeSimilar', $itemCodeSimilar);
                    })->orWhere(function ($q2) use ($itemCode, $itemCodeSimilar) {
                        $q2->where('ItemCode', $itemCodeSimilar)->where('ItemCodeSimilar', $itemCode);
                    });
                })
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Pasangan item similar ini sudah terdaftar!']);
            }

            DB::table('master_item_similar')->where('id', $id)->update([
                'ItemCode'        => $itemCode,
                'ItemCodeSimilar' => $itemCodeSimilar,
                'updated_at'      => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Hapus relasi similar.
     */
    public function destroySimilar($id)
    {
        try {
            DB::table('master_item_similar')->where('id', $id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Ambil list ItemCode yang ada di master_items untuk dropdown/autocomplete.
     */
    public function getItemCodeList()
    {
        $data = DB::table('master_items')
            ->whereNotNull('item_code_desc')
            ->where('item_code_desc', '!=', '')
            ->select('item_code', 'item_code_desc', 'description', 'product', 'type', 'brand', 'grade')
            ->orderBy('item_code_desc')
            ->get();
        return response()->json($data);
    }
}
