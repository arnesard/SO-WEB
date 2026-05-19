<?php

namespace App\Services\data_master;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportDailyService
{
    public function processUpload($file)
    {
        $content = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $reportDate = null;
        $transDate = null;
        $plant = "Plant B";
        $gtType = null;
        $dataToInsert = [];

        $clean = function ($line, $start, $length) {
            if (strlen($line) < $start) return 0;
            $val = substr($line, $start, $length);
            $val = str_replace([' ', ','], '', trim($val));
            return is_numeric($val) ? (int)$val : 0;
        };

        foreach ($content as $line) {
            if (!$reportDate && preg_match('/Date\s*:\s*([0-9A-Z-]+)/i', $line, $matches)) {
                try {
                    $reportDate = Carbon::parse($matches[1])->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }
            if (!$transDate && preg_match('/Trans Date\s*:\s*([0-9A-Z-]+)/i', $line, $matches)) {
                try {
                    $transDate = Carbon::parse($matches[1])->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }
            if (str_contains($line, 'GT TYPE :')) {
                $parts = explode(':', $line);
                $gtType = isset($parts[1]) ? trim($parts[1]) : $gtType;
                continue;
            }

            $trimmedLine = ltrim($line);
            if (preg_match('/^[A-Z0-9]{3,}/', $trimmedLine) && !str_contains($line, 'Sub Total') && !str_contains($line, 'Item   Description') && !str_contains($line, 'Pages :') && strlen($line) > 140) {
                $dataToInsert[] = [
                    'report_date'      => $reportDate,
                    'transaction_date' => $transDate,
                    'plant'            => $plant,
                    'gt_type'          => $gtType,
                    'item_code'        => trim(substr($line, 0, 12)),
                    'description'      => trim(substr($line, 12, 38)),
                    'oe_stk_awal'      => $clean($line, 50, 10),
                    'oe_in'            => $clean($line, 60, 10),
                    'oe_out'           => $clean($line, 70, 10),
                    'oe_adj'           => $clean($line, 80, 10),
                    'oe_stk_akhir'     => $clean($line, 90, 10),
                    'ok_stk_awal'      => $clean($line, 100, 12),
                    'ok_in'            => $clean($line, 112, 11),
                    'ok_out'           => $clean($line, 123, 11),
                    'ok_adj'           => $clean($line, 134, 10),
                    'ok_stk_akhir'     => $clean($line, 144, 10),
                    'nd_stk_awal'      => $clean($line, 154, 10),
                    'nd_in'            => $clean($line, 164, 8),
                    'nd_out'           => $clean($line, 172, 8),
                    'nd_adj'           => $clean($line, 180, 9),
                    'nd_stk_akhir'     => $clean($line, 189, 10),
                ];
            }
        }

        if (empty($dataToInsert)) return 0;

        DB::transaction(function () use ($dataToInsert, $transDate) {
            $newItemCodes = array_column($dataToInsert, 'item_code');
            DB::table('report_daily_transactions')->where('transaction_date', $transDate)->whereNotIn('item_code', $newItemCodes)->delete();

            $newResumeItemCodeDescs = [];
            foreach ($dataToInsert as $row) {
                if ($row['oe_stk_akhir'] > 0) $newResumeItemCodeDescs[] = $row['item_code'] . '-0';
                if ($row['ok_stk_akhir'] > 0) $newResumeItemCodeDescs[] = $row['item_code'] . '-1';
            }
            DB::table('report_daily_transactions_stk_akhir')->where('transaction_date', $transDate)->whereNotIn('item_code_desc', $newResumeItemCodeDescs)->delete();

            foreach ($dataToInsert as $row) {
                // 1. PROSES TABEL UTAMA
                // Cek apakah data sudah ada
                $existsMain = DB::table('report_daily_transactions')
                    ->where('transaction_date', $row['transaction_date'])
                    ->where('item_code', $row['item_code'])
                    ->exists();

                if (!$existsMain) {
                    // Jika data baru, isi created_at dan updated_at
                    DB::table('report_daily_transactions')->insert(
                        array_merge($row, ['created_at' => now(), 'updated_at' => now()])
                    );
                } else {
                    // Jika sudah ada, cukup update datanya dan updated_at
                    DB::table('report_daily_transactions')
                        ->where('transaction_date', $row['transaction_date'])
                        ->where('item_code', $row['item_code'])
                        ->update(array_merge($row, ['updated_at' => now()]));
                }

                // 2. PROSES TABEL STOK AKHIR (RESUME)
                $types = ['-0' => $row['oe_stk_akhir'], '-1' => $row['ok_stk_akhir']];
                foreach ($types as $suffix => $qty) {
                    if ($qty > 0) {
                        $fullDesc = $row['item_code'] . $suffix;

                        $existsResume = DB::table('report_daily_transactions_stk_akhir')
                            ->where('transaction_date', $row['transaction_date'])
                            ->where('item_code_desc', $fullDesc)
                            ->exists();

                        $resumeData = [
                            'item_code' => $row['item_code'],
                            'description' => $row['description'],
                            'qty' => $qty,
                            'updated_at' => now()
                        ];

                        if (!$existsResume) {
                            // Insert data baru
                            DB::table('report_daily_transactions_stk_akhir')->insert(
                                array_merge($resumeData, [
                                    'transaction_date' => $row['transaction_date'],
                                    'item_code_desc' => $fullDesc,
                                    'created_at' => now()
                                ])
                            );
                        } else {
                            // Update data ada
                            DB::table('report_daily_transactions_stk_akhir')
                                ->where('transaction_date', $row['transaction_date'])
                                ->where('item_code_desc', $fullDesc)
                                ->update($resumeData);
                        }
                    }
                }
            }
        });

        return count($dataToInsert);
    }
}
