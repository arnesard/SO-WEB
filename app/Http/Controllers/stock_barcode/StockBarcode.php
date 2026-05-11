<?php

namespace App\Http\Controllers\stock_barcode;

use App\Http\Controllers\Controller;


class StockBarcode extends Controller
{
    public function index()
    {
        // Data dummy buat ngetes tampilan card
        $stocks = [
            ['kode' => 'BRG-001', 'nama' => 'Ban Gajah Tunggal A1', 'stok' => 150, 'rak' => 'A-01'],
            ['kode' => 'BRG-002', 'nama' => 'Ban Gajah Tunggal B2', 'stok' => 85, 'rak' => 'B-05'],
            ['kode' => 'BRG-003', 'nama' => 'Ban Gajah Tunggal C3', 'stok' => 210, 'rak' => 'C-02'],
        ];

        return view('stock_barcode.stock_barcode', compact('stocks'));
    }
}
