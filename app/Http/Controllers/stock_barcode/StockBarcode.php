<?php

namespace App\Http\Controllers\stock_barcode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class StockBarcode extends Controller
{
    public function index()
    {
        // Ambil summary global
        $summary = DB::table('stock_barcodes_auto_create')
            ->select(
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('COUNT(DISTINCT item) as total_sku')
            )->first();

        return view('stock_barcode.stock_barcode', compact('summary'));
    }

    public function triggerSync()
    {
        set_time_limit(600);
        try {
            // Kita jalankan Command yang tadi dibuat
            Artisan::call('stock:sync-barcode');
            return response()->json([
                'status' => 'success',
                'message' => 'Salin data dari Database Server berhasil!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
