<?php

namespace App\Http\Controllers\dashboard\oracle_vs_fisik;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OracleVsFisikBarcodeMonstockController extends Controller
{
    /**
     * Mengambil seluruh data mentah monstock + JOIN description potong buntut master size
     */
    public function getData()
    {
        // 🎯 RUMUS SAKTI KEBANGLKITAN DESKRIPSI: Potong buntut m.item biar polosan, baru sandingkan dengan b.item mentah!
        $mainData = DB::table('so_all_wh_barcode_monstock_db as b')
            ->leftJoin('so_all_wh_master_size_db as m', function ($join) {
                $join->on('b.item', '=', DB::raw("SUBSTRING_INDEX(m.item, '-', 1)")) // 🔒 POTONG DI SINI BRO! 🔒
                    ->on('b.warehouse', '=', 'm.warehouse'); // Sekat gudang dikunci steril
            })
            ->select(
                'b.id',
                'b.warehouse',
                'b.rackcode',
                'b.item',
                'b.jml',
                'b.oem',
                'b.loccode',
                'm.description' // Kolom deskripsi ditarik utuh tanpa drama!
            )
            ->orderBy('b.id', 'desc')
            ->get();

        $uniqueWh = DB::table('so_all_wh_barcode_monstock_db')
            ->whereNotNull('warehouse')
            ->where('warehouse', '!=', '')
            ->distinct()
            ->orderBy('warehouse', 'asc')
            ->pluck('warehouse');

        $uniqueRack = DB::table('so_all_wh_barcode_monstock_db')
            ->whereNotNull('rackcode')
            ->where('rackcode', '!=', '')
            ->distinct()
            ->orderBy('rackcode', 'asc')
            ->pluck('rackcode');

        return response()->json([
            'master_data' => $mainData,
            'filter_wh'   => $uniqueWh,
            'filter_rack' => $uniqueRack
        ]);
    }

    /**
     * Proses Import Massal - Mengisi so_all_wh_barcode_monstock_db (Mentah) & so_all_wh_barcode_monstock_auto_db (Grouping)
     */
    public function import(Request $request)
    {
        if (!$request->hasFile('file_csv')) {
            return response()->json(['status' => 'error', 'message' => 'Berkas CSV tidak terdeteksi oleh sistem!'], 400);
        }

        $targetWarehouse = strtoupper(trim($request->target_warehouse));
        if (empty($targetWarehouse)) {
            return response()->json(['status' => 'error', 'message' => 'Target Warehouse wajib lu pilih !'], 400);
        }

        try {
            $file = $request->file('file_csv');
            $handle = fopen($file->getRealPath(), "r");

            // --- TAHAP 1: PEMETAAN STRUKTUR HEADER BERKAS CSV ---
            $headerLine = fgetcsv($handle, 1000, ",");
            if (!$headerLine) {
                fclose($handle);
                return response()->json(['status' => 'error', 'message' => 'File CSV kosong !'], 400);
            }

            $headers = array_map(function ($h) {
                return strtolower(trim(str_replace(['"', "'"], '', $h)));
            }, $headerLine);

            $idxRack    = array_search('rackcode', $headers);
            $idxItem    = array_search('item', $headers);
            $idxJml     = array_search('jml', $headers);
            $idxOem     = array_search('oem', $headers);
            $idxLoccode = array_search('loccode', $headers);

            if ($idxItem === false || $idxJml === false) {
                fclose($handle);
                return response()->json(['status' => 'error', 'message' => 'Format file salah! Kolom Item atau Jml tidak ditemukan.'], 400);
            }

            // 🎯 SATPAM VALIDASI INTERIOR FILE CSV VS DROPDOWN TARGET WAREHOUSE 🎯
            $csvWarehouseDetected = null;
            while (($checkRow = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (!isset($checkRow[$idxItem]) || trim($checkRow[$idxItem]) === '') continue;
                $checkLoc = ($idxLoccode !== false && isset($checkRow[$idxLoccode])) ? trim(str_replace('"', '', $checkRow[$idxLoccode])) : '';

                if (!empty($checkLoc) && $checkLoc !== '-' && $checkLoc !== '~') {
                    $csvWarehouseDetected = strtoupper(substr($checkLoc, 0, 3));
                    break;
                }
            }

            rewind($handle);
            fgetcsv($handle, 1000, ","); // Skip header baris pertama lagi

            if ($csvWarehouseDetected !== null) {
                if ($csvWarehouseDetected !== $targetWarehouse) {
                    fclose($handle);
                    return response()->json([
                        'status' => 'error',
                        'message' => "⚠️ PENTING BRO! Lu milih Gudang [{$targetWarehouse}], tapi file CSV yang lu upload terdeteksi milik Gudang [{$csvWarehouseDetected}]. Proses diblokir sistem biar data lu kagak kehapus salah!"
                    ], 400);
                }
            } else {
                fclose($handle);
                return response()->json([
                    'status' => 'error',
                    'message' => "Gagal Validasi! Sistem tidak menemukan data Kode Lokasi (loccode) yang valid di dalam file CSV lu bro."
                ], 400);
            }

            // 🎯 KUNCI AMAN: Ambil sejarah no_doc lama dari DB terikat kombinasi Lokasi @ Item
            $historicalDocMap = DB::table('so_all_wh_barcode_monstock_auto_db')
                ->where('warehouse', $targetWarehouse)
                ->whereNotNull('no_doc')
                ->where('no_doc', '!=', '-')
                ->select('loccode', 'item', 'no_doc')
                ->distinct()
                ->get()
                ->mapWithKeys(function ($item) {
                    $key = strtoupper(trim($item->loccode)) . '@' . strtoupper(trim($item->item));
                    return [$key => $item->no_doc];
                })->toArray();

            // --- TAHAP 2: BENTENG BILAS DATA LAMA ---
            DB::table('so_all_wh_barcode_monstock_db')->where('warehouse', $targetWarehouse)->delete();
            DB::table('so_all_wh_barcode_monstock_auto_db')->where('warehouse', $targetWarehouse)->delete();

            $batchMain = [];
            $compressedAutoMap = [];
            $lastValidLoccode = $targetWarehouse . '-UNKNOWN';
            $maxSequencePerPrefix = [];

            // --- TAHAP 3: PARSING BERKAS CSV ---
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (!isset($row[$idxItem]) || trim($row[$idxItem]) === '') continue;

                $loccodeRaw = ($idxLoccode !== false && isset($row[$idxLoccode])) ? trim(str_replace('"', '', $row[$idxLoccode])) : '';
                $isLoccodeKosongAtauCacing = (empty($loccodeRaw) || $loccodeRaw === '-' || $loccodeRaw === '~');

                if (!$isLoccodeKosongAtauCacing) {
                    $lastValidLoccode = strtoupper($loccodeRaw);
                } else {
                    $lastValidLoccode = (!empty($lastValidLoccode) && $lastValidLoccode !== '-') ? $lastValidLoccode : $targetWarehouse . '-AUTO';
                }

                $rackcode = ($idxRack !== false && isset($row[$idxRack])) ? strtoupper(trim(str_replace(['"'], '', $row[$idxRack]))) : '-';
                $item     = strtoupper(trim(str_replace('"', '', $row[$idxItem])));
                $jml      = isset($row[$idxJml]) ? intval(trim($row[$idxJml])) : 0;
                $oem      = ($idxOem !== false && isset($row[$idxOem])) ? intval(trim($row[$idxOem])) : 0;

                // A. Semburkan data mentah murni asli ke tabel utama
                $batchMain[] = [
                    'warehouse'  => $targetWarehouse,
                    'rackcode'   => $rackcode,
                    'item'       => $item,
                    'jml'        => $jml,
                    'oem'        => $oem,
                    'loccode'    => $lastValidLoccode,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // B. LOGIKA BYPASS GRADE OEM
                $isForcedOem = (substr($item, 0, 2) === 'TH' || substr($item, -2) === 'SP');
                $loccodeForAuto = $isLoccodeKosongAtauCacing ? (!empty($loccodeRaw) ? $loccodeRaw : '-') : $lastValidLoccode;

                $rawSplitRecords = [];
                if ($isForcedOem) {
                    $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $jml];
                } else {
                    if ($oem == $jml) {
                        $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $oem];
                    } elseif ($oem == 0) {
                        $rawSplitRecords[] = ['item' => $item . '-1', 'qty' => $jml];
                    } else {
                        $rawSplitRecords[] = ['item' => $item . '-0', 'qty' => $oem];
                        $rawSplitRecords[] = ['item' => $item . '-1', 'qty' => ($jml - $oem)];
                    }
                }

                // C. PROSES KONSOLIDASI AGREGASI UNTUK TABEL AUTO
                foreach ($rawSplitRecords as $split) {
                    $fingerprintKey = $targetWarehouse . '@' . $split['item'] . '@' . $loccodeForAuto;

                    if (!isset($compressedAutoMap[$fingerprintKey])) {
                        $compressedAutoMap[$fingerprintKey] = [
                            'warehouse'  => $targetWarehouse,
                            'item'       => $split['item'],
                            'Qty'        => $split['qty'],
                            'Rak'        => 1,
                            'loccode'    => $loccodeForAuto,
                            'no_doc'     => '-',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    } else {
                        $compressedAutoMap[$fingerprintKey]['Qty'] += $split['qty'];
                        $compressedAutoMap[$fingerprintKey]['Rak'] += 1;
                    }
                }

                if (count($batchMain) >= 500) {
                    DB::table('so_all_wh_barcode_monstock_db')->insert($batchMain);
                    $batchMain = [];
                }
            }
            fclose($handle);

            if (!empty($batchMain)) {
                DB::table('so_all_wh_barcode_monstock_db')->insert($batchMain);
            }

            // D. SEKUENS GENERATE NO_DOC PER BARIS GRUPPING AUTO
            if (!empty($compressedAutoMap)) {
                $batchAutoFinal = array_values($compressedAutoMap);

                usort($batchAutoFinal, function ($a, $b) {
                    return strcmp($a['loccode'], $b['loccode']);
                });

                foreach ($batchAutoFinal as &$finalRow) {
                    $loccodeActive = $finalRow['loccode'];
                    $itemActive    = $finalRow['item'];

                    if ($loccodeActive === '-' || $loccodeActive === '~' || empty($loccodeActive)) {
                        $finalRow['no_doc'] = $loccodeActive;
                        continue;
                    }

                    $historyKey = $loccodeActive . '@' . $itemActive;

                    if (isset($historicalDocMap[$historyKey])) {
                        $finalRow['no_doc'] = $historicalDocMap[$historyKey];
                    } else {
                        $prefixDoc = 'G';
                        $char5     = (strlen($loccodeActive) >= 5) ? substr($loccodeActive, 4, 1) : '0';
                        $suffixLoc = (strlen($loccodeActive) >= 7) ? substr($loccodeActive, 6) : 'UNKNOWN';
                        $prefixNoDoc = $prefixDoc . $char5 . $suffixLoc;

                        if (!isset($maxSequencePerPrefix[$prefixNoDoc])) {
                            $maxSequencePerPrefix[$prefixNoDoc] = 0;

                            foreach ($historicalDocMap as $oldDoc) {
                                if (strpos($oldDoc, $prefixNoDoc) === 0) {
                                    $seqNumber = intval(substr($oldDoc, -2));
                                    if ($seqNumber > $maxSequencePerPrefix[$prefixNoDoc]) {
                                        $maxSequencePerPrefix[$prefixNoDoc] = $seqNumber;
                                    }
                                }
                            }
                        }

                        $maxSequencePerPrefix[$prefixNoDoc]++;
                        $generatedNoDoc = $prefixNoDoc . str_pad($maxSequencePerPrefix[$prefixNoDoc], 2, '0', STR_PAD_LEFT);

                        $finalRow['no_doc'] = $generatedNoDoc;
                        $historicalDocMap[$historyKey] = $generatedNoDoc;
                    }
                }
                unset($finalRow);

                foreach (array_chunk($batchAutoFinal, 500) as $chunkAuto) {
                    DB::table('so_all_wh_barcode_monstock_auto_db')->insert($chunkAuto);
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Gudang ' . $targetWarehouse . ' sukses dibilas bersih & data auto siap saji!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error Backend SQL: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::table('so_all_wh_barcode_monstock_db')->where('id', $id)->delete();
            return response()->json(['status' => 'success', 'message' => 'Baris barcode berhasil dibuang!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal hapus: ' . $e->getMessage()], 500);
        }
    }
}
