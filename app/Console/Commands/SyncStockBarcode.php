<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStockBarcode extends Command
{
    protected $signature = 'stock:sync-barcode';
    protected $description = 'Full Refresh & Rebuild Stock Barcode from Production';

    public function handle()
    {
        $this->info("=== MEMULAI PROSES PENYELARASAN DATA ===");

        try {
            // 1. Set Isolation Level agar tidak mengganggu transaksi di Produksi
            DB::connection('mysql_second')->statement('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');

            // 2. Kosongkan Tabel Lama (Hapus yang semula)
            $this->warn("Mengosongkan data lama di local...");
            DB::table('stock_barcodes_auto_create')->truncate();

            // 3. Tarik Hasil Resume dari Server Produksi (.126)
            $this->info("Menghitung ulang 70 Juta baris di Server Produksi... (Sabar ya bro)");

            $summaryData = DB::connection('mysql_second')->table('rack')
                ->select([
                    'rackcode',
                    DB::raw("CONCAT(item, IF(LEFT(probcode, 2) = 'OE', '-0', '-1')) AS item_final"),
                    DB::raw("COUNT(*) as total_qty")
                ])
                ->where(function ($q) {
                    $q->where('rackcode', 'like', 'B%')
                        ->orWhere('rackcode', 'like', '~%');
                })
                ->groupBy('rackcode', 'item_final')
                ->get();

            $totalData = $summaryData->count();
            $this->info("Kalkulasi Selesai. Ditemukan $totalData baris resume.");

            // 4. Simpan ke database Lokal (.179) secara Batch
            $this->info("Menyimpan data ke Lokal...");
            foreach ($summaryData->chunk(500) as $chunk) {
                $batch = [];
                foreach ($chunk as $row) {
                    $batch[] = [
                        'rackcode' => $row->rackcode,
                        'item'     => $row->item_final,
                        'qty'      => $row->total_qty
                    ];
                }
                DB::table('stock_barcodes_auto_create')->insert($batch);
            }

            $this->info("=== MISSION ACCOMPLISHED! DATA BERHASIL DISINKRONKAN ===");
        } catch (\Exception $e) {
            $this->error("Waduh Error Bro: " . $e->getMessage());
        }
    }
}
