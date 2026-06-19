<?php

namespace App\Http\Controllers\so_karantina;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadBstbController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file_excel');

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $dataToInsert = [];
            $currentTimestamp = Carbon::now()->format('d/m/Y H:i');

            foreach ($rows as $index => $row) {
                if ($index === 0) continue;
                if (empty(array_filter($row))) continue;

                $tglRaw = $row[0];
                $tglParts = explode('-', $tglRaw);

                $tgl = $tglParts[0] ?? null;
                $shift = $tglParts[1] ?? null;

                $dataToInsert[] = [
                    'tgl'            => $tgl,
                    'shift'          => $shift,
                    'plant'          => $row[1],
                    'opr_prod'       => $row[2],
                    'oprname_prod'   => $row[3],
                    'item_code_desc' => $row[4],
                    'QtyStk'         => isset($row[23]) ? (int)$row[23] : 0,
                    'txndate'        => $currentTimestamp,
                ];
            }

            // PROSES PALING CEPAT: Tanpa Transaction, langsung Truncate & Insert
            // 1. Kosongkan tabel lama sampai bersih (ID reset ke 1)
            DB::connection('so_karantina')->table('so_karantina_bstb')->truncate();

            // 2. Masukkan data baru dari Excel sekaligus (batch insert)
            if (!empty($dataToInsert)) {
                DB::connection('so_karantina')->table('so_karantina_bstb')->insert($dataToInsert);
            }

            return back()->with('success', 'Data lama berhasil dibersihkan dan data Excel baru berhasil disimpan!');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal memproses Excel: ' . $e->getMessage());
        }
    }
}
