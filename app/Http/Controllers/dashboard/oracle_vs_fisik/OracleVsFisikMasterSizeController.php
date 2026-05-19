<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikMasterSizeController extends Controller
{
    /**
     * Mengambil data master size beserta list unik warehouse & grade untuk filter
     */
    public function getData()
    {
        // 1. Ambil data utama master size
        $mainData = DB::table('so_all_wh_master_size_db')->orderBy('id', 'desc')->get();

        // 2. Ambil daftar unik Warehouse yang ada isi datanya (tidak null / kosong)
        $uniqueWh = DB::table('so_all_wh_master_size_db')
            ->select('warehouse')
            ->whereNotNull('warehouse')
            ->where('warehouse', '!=', '')
            ->distinct()
            ->orderBy('warehouse', 'asc')
            ->pluck('warehouse');

        // 3. Ambil daftar unik Grade yang ada isi datanya
        $uniqueGrade = DB::table('so_all_wh_master_size_db')
            ->select('grade')
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->distinct()
            ->orderBy('grade', 'asc')
            ->pluck('grade');

        // Balikin semua datanya dalam satu response JSON
        return response()->json([
            'master_data'  => $mainData,
            'filter_wh'    => $uniqueWh,
            'filter_grade' => $uniqueGrade
        ]);
    }

    /**
     * Memproses data baru + Perbaikan 3: Intersepsi null menjadi default "-"
     */
    public function store(Request $request)
    {
        try {
            // Lakukan normalisasi string: Jika null, kosong, atau spasi doang, paksa ganti jadi "-"
            $product  = !empty(trim($request->product))  ? trim($request->product)  : '-';
            $type     = !empty(trim($request->type))     ? trim($request->type)     : '-';
            $brand    = !empty(trim($request->brand))    ? trim($request->brand)    : '-';
            $category = !empty(trim($request->category)) ? trim($request->category) : '-';

            // Bangun pattern otomatis berdasarkan 4 variabel di atas
            $patternParts = [$product, $type, $brand, $category];

            // Filter menghilangkan tanda strip untuk pembentukan teks pattern murni
            $cleanParts = array_filter($patternParts, function ($value) {
                return !empty($value) && $value !== '-';
            });

            $pattern = implode(' ', $cleanParts);

            DB::table('so_all_wh_master_size_db')->insert([
                'warehouse'   => $request->warehouse,
                'item'        => trim($request->item),
                'description' => trim($request->description),
                'grade'       => $request->grade,
                'product'     => $product,
                'type'        => $type,
                'brand'       => $brand,
                'category'    => $category,
                'pattern'     => !empty($pattern) ? trim($pattern) : null,
                'created_at'  => now(),
                'updated_at'  => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Data Master Size berhasil disimpan!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memperbarui data lama + Perbaikan 3: Intersepsi null menjadi default "-"
     */
    public function update(Request $request, $id)
    {
        try {
            $product  = !empty(trim($request->product))  ? trim($request->product)  : '-';
            $type     = !empty(trim($request->type))     ? trim($request->type)     : '-';
            $brand    = !empty(trim($request->brand))    ? trim($request->brand)    : '-';
            $category = !empty(trim($request->category)) ? trim($request->category) : '-';

            $patternParts = [$product, $type, $brand, $category];
            $cleanParts = array_filter($patternParts, fn($v) => !empty($v) && $v !== '-');
            $pattern = implode(' ', $cleanParts);

            DB::table('so_all_wh_master_size_db')->where('id', $id)->update([
                'warehouse'   => $request->warehouse,
                'item'        => trim($request->item),
                'description' => trim($request->description),
                'grade'       => $request->grade,
                'product'     => $product,
                'type'        => $type,
                'brand'       => $brand,
                'category'    => $category,
                'pattern'     => !empty($pattern) ? trim($pattern) : null,
                'updated_at'  => now()
            ]);

            return response()->json(['status' => 'success', 'message' => 'Data Master Size berhasil diupdate!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('so_all_wh_master_size_db')->where('id', $id)->delete();
            return response()->json(['status' => 'success', 'message' => 'Data Master Size berhasil dihapus!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error Backend: ' . $e->getMessage()], 500);
        }
    }
}
