<?php

namespace App\Http\Controllers\data_master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UploadStockBarcodeController extends Controller
{
    public function upload(Request $request)
    {
        if (!$request->hasFile('file_barcode')) {
            return response()->json(['success' => false, 'message' => 'File CSV tidak ditemukan'], 400);
        }

        $tgl = $request->input('date');

        try {
            $file = $request->file('file_barcode');
            $lines = file($file->getRealPath());
            array_shift($lines); // Buang header

            $incomingData = [];
            $currentKeys = [];

            foreach ($lines as $line) {
                $data = str_getcsv($line, ",");
                if (count($data) >= 15) {
                    $rack = trim($data[0]);
                    $item = str_replace('"', '', trim($data[1]));
                    $key = $rack . $item;

                    $incomingData[] = [
                        'rack_code'        => $rack,
                        'item_code'        => $item,
                        'transaction_date' => $tgl,
                        'whs_week'         => str_replace('"', '', trim($data[2])),
                        'cur_week'         => str_replace('"', '', trim($data[3])),
                        'qty'              => (int)$data[4],
                        'qc'               => (int)$data[5],
                        'qa'               => (int)$data[6],
                        'qaa'              => (int)$data[7],
                        'rnd'              => (int)$data[8],
                        'holds'            => (int)$data[9],
                        'oem'              => (int)$data[10],
                        'ng'               => (int)$data[11],
                        'booking'          => trim($data[12]),
                        'loc_code'         => str_replace('"', '', trim($data[13])),
                        'hold_days'        => (int)$data[14],
                        'updated_at'       => now(),
                    ];
                    $currentKeys[$key] = true;
                }
            }

            DB::transaction(function () use ($incomingData, $tgl, $currentKeys) {
                // --- BAGIAN 1: TABEL UTAMA (stock_barcodes) ---
                $existingInDb = DB::table('stock_barcodes')->where('transaction_date', $tgl)->select('rack_code', 'item_code')->get();
                foreach ($existingInDb as $row) {
                    if (!isset($currentKeys[$row->rack_code . $row->item_code])) {
                        DB::table('stock_barcodes')->where('transaction_date', $tgl)->where('rack_code', $row->rack_code)->where('item_code', $row->item_code)->delete();
                    }
                }

                foreach (array_chunk($incomingData, 500) as $chunk) {
                    foreach ($chunk as $row) {
                        $exists = DB::table('stock_barcodes')->where('transaction_date', $row['transaction_date'])->where('rack_code', $row['rack_code'])->where('item_code', $row['item_code'])->first();
                        if ($exists) {
                            DB::table('stock_barcodes')->where('id', $exists->id)->update($row);
                        } else {
                            $row['created_at'] = now();
                            DB::table('stock_barcodes')->insert($row);
                        }
                    }
                }

                // --- BAGIAN 2: TABEL RESUME (stock_barcodes_resume) ---

                // A. AMBIL DATA LAMA BUAT "INGATAN" (Memory)
                $oldResumes = DB::table('stock_barcodes_resume')
                    ->where('transaction_date', $tgl)
                    ->get()
                    ->keyBy(function ($item) {
                        return $item->rack_code . $item->item_code_desc;
                    });

                // B. HAPUS SEMUA RESUME LAMA (Kita akan re-insert tapi pake created_at lama)
                DB::table('stock_barcodes_resume')->where('transaction_date', $tgl)->delete();

                foreach ($incomingData as $row) {
                    $master = DB::table('master_items')->where('item_code', $row['item_code'])->first();
                    $category = $master ? strtoupper($master->category) : '';

                    $qtyTotal = $row['qty'];
                    $qtyOem   = $row['oem'];
                    $qtyOk    = $qtyTotal - $qtyOem;

                    $resumeBase = [
                        'transaction_date' => $tgl,
                        'rack_code'        => $row['rack_code'],
                        'loc_code'         => $row['loc_code'],
                        'item_code'        => $row['item_code'],
                    ];

                    if ($category == 'SPAREPART' || $category == 'IMPORT') {
                        $this->smartInsertResume($resumeBase, $row['item_code'] . '-0', $qtyTotal, $oldResumes);
                    } else {
                        if ($qtyOk > 0) {
                            $this->smartInsertResume($resumeBase, $row['item_code'] . '-1', $qtyOk, $oldResumes);
                        }
                        if ($qtyOem > 0) {
                            $this->smartInsertResume($resumeBase, $row['item_code'] . '-0', $qtyOem, $oldResumes);
                        }
                    }
                }
            });

            return response()->json(['success' => true, 'count' => count($incomingData)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function smartInsertResume($base, $itemCodeDesc, $qty, $oldResumes)
    {
        $desc = DB::table('master_items')->where('item_code_desc', $itemCodeDesc)->value('description');

        // Cek di "Ingatan" (data lama), apakah Rack + ItemDesc ini sudah pernah ada?
        $key = $base['rack_code'] . $itemCodeDesc;

        if (isset($oldResumes[$key])) {
            // Jika ADA, ambil created_at yang lama
            $createdAt = $oldResumes[$key]->created_at;
        } else {
            // Jika BARU, buat created_at sekarang
            $createdAt = now();
        }

        DB::table('stock_barcodes_resume')->insert(array_merge($base, [
            'item_code_desc' => $itemCodeDesc,
            'description'    => $desc ?? '-',
            'qty'            => $qty,
            'created_at'     => $createdAt, // Pakai tanggal lahir lama/baru
            'updated_at'     => now()       // Selalu update waktu sekarang
        ]));
    }

    public function list(Request $request)
    {
        $date = $request->date;
        $search = $request->search;

        $query = DB::table('stock_barcodes')
            ->where('transaction_date', $date);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                    ->orWhere('rack_code', 'like', "%{$search}%");
            });
        }

        // Hitung total sebelum di-paginate
        $totals = (clone $query)->select(
            DB::raw('COUNT(*) as total_rack'),
            DB::raw('SUM(qty) as total_qty'),
            DB::raw('SUM(oem) as total_oem')
        )->first();

        $data = $query->orderBy('rack_code', 'asc')->paginate(100);

        // Masukkan data hitungan ke dalam response JSON
        return response()->json([
            'data' => $data->items(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'total' => $data->total(),
            'summary' => [
                'total_rack' => $totals->total_rack ?? 0,
                'total_qty'  => $totals->total_qty ?? 0,
                'total_oem'  => $totals->total_oem ?? 0,
            ]
        ]);
    }

    public function getCalendarBC(Request $request)
    {
        $year = $request->year;
        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
        $dates = DB::table('stock_barcodes')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->distinct()->pluck('transaction_date')->toArray();
        return response()->json($dates);
    }
}
