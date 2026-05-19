<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\appkso\Appkso;
use App\Http\Controllers\stock_barcode\StockBarcode;
use App\Http\Controllers\data_master\ReportDailyController;
use App\Http\Controllers\data_master\MasterItemController;
use App\Http\Controllers\data_master\UploadStockBarcodeController;
use App\Http\Controllers\dashboard\OracleVsBarcodeController;
use App\Http\Controllers\dashboard\OracleVsFisikController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikMasterSizeController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikBarcodeMonstockController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikAppksoController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikPicController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikTagStockController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. DASHBOARD UTAMA & MODUL ORACLE VS BARCODE
// =========================================================================
Route::controller(OracleVsBarcodeController::class)->group(function () {
    Route::get('/', 'index')->name('dashboard.index');
    Route::get('/dashboard', 'index');
    Route::get('/oracle-barcode', 'index')->name('oracle_barcode.index');
    Route::get('/oracle-barcode/chart-data', 'getChartData')->name('oracle_barcode.chart');
    Route::get('/oracle-barcode/detail-pattern', 'getDetailByPattern')->name('oracle-barcode.detail');
    Route::get('/oracle-barcode/deep-detail', 'getDeepDetailBarcode')->name('oracle-barcode.deep-detail');
});


// =========================================================================
// 2. MODUL ACUAN UTAMA ORACLE VS FISIK
// =========================================================================
Route::controller(OracleVsFisikController::class)->group(function () {
    Route::get('/oracle-fisik', 'index')->name('oracle_fisik.index');
    Route::get('/oracle-fisik/data', 'getFisikData')->name('oracle_fisik.data');
    Route::get('/oracle-fisik/switch-menu', 'switchMenu')->name('oracle_fisik.switch');
});


// =========================================================================
// 3. ROUTE KHUSUS SUB-MODUL INTERNAL FISIK (PREFIXED & RE-GROUPED)
// =========================================================================
Route::prefix('oracle-fisik')->name('oracle_fisik.')->group(function () {

    // 3.1. Master Size Area
    Route::controller(OracleVsFisikMasterSizeController::class)->prefix('master-size')->name('master_size.')->group(function () {
        Route::get('/data', 'getData')->name('data');
        Route::post('/store', 'store')->name('store')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Route::post('/update/{id}', 'update')->name('update')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Route::delete('/delete/{id}', 'destroy')->name('delete')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    });

    // 3.2. Barcode Monitoring Stock Area
    Route::controller(OracleVsFisikBarcodeMonstockController::class)->prefix('barcode')->name('barcode.')->group(function () {
        Route::get('/data', 'getData')->name('data');
        Route::post('/import', 'import')->name('import')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Route::delete('/delete/{id}', 'destroy')->name('delete')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    });

    // 3.3. Master PIC & Auditor Area
    Route::controller(OracleVsFisikPicController::class)->prefix('pic')->name('pic.')->group(function () {
        Route::get('/data', 'getData')->name('data');
        Route::post('/store', 'storeOrUpdate')->name('store')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Route::delete('/delete/{id}', 'destroy')->name('delete')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    });

    // 3.4. APPKSO Import Area
    Route::controller(OracleVsFisikAppksoController::class)
        ->prefix('appkso')
        ->name('appkso.')
        ->group(function () {

            Route::post('/import', 'import')->name('import');
            Route::get('/data', 'getData')->name('data');
            Route::get('/documents', [OracleVsFisikTagStockController::class, 'getDocuments']);
        });

    Route::controller(OracleVsFisikTagStockController::class)
        ->prefix('tagstock')
        ->name('tagstock.')
        ->group(function () {

            Route::match(['get', 'post'], '/print', 'printTagStock');
        });


    // 3.5. Modul Cetak Tag Stock Mandiri (Baru Eksklusif)
    Route::controller(OracleVsFisikTagStockController::class)->prefix('tagstock')->name('tagstock.')->group(function () {
        Route::get('/init-filters', 'initFilters');
        Route::get('/operators', 'getOperatorsByWarehouse'); // 🎯 DAFTARKAN INI UNTUK DROPDOWN BERANTAI TIM PIC 🎯
        Route::get('/operator-details', 'getOperatorDetails');
        Route::post('/process-rows', 'processRows')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    });
});


// =========================================================================
// 4. MODUL SCAN APPKSO UTAMA
// =========================================================================
Route::prefix('appkso')->group(function () {
    Route::get('/', [Appkso::class, 'index'])->name('appkso.index');
    Route::post('/fetch-tag-data', [Appkso::class, 'fetchTagKSOData'])->name('appkso.fetch-tag-data');
    Route::post('/print-preview', [Appkso::class, 'generatePrintPreview'])->name('appkso.print-preview');
    Route::get('/rekap-kso', [Appkso::class, 'rekapKso']);
    Route::post('/print-rekap-preview', [Appkso::class, 'generateRekapPreview'])->name('appkso.print-rekap-preview');
});
Route::get('/appkso', [Appkso::class, 'index'])->name('appkso.index');


// =========================================================================
// 5. MODUL SYNC STOCK BARCODE WIRELESS
// =========================================================================
Route::controller(StockBarcode::class)->group(function () {
    Route::get('/stock-barcode', 'index')->name('stock.barcode');
    Route::post('/stock-barcode/sync', 'triggerSync')->name('stock.barcode.sync');
});


// =========================================================================
// 6. GROUPING UTAMA DATA MASTER GUDANG (REPORT, DATA ITEM, CALENDAR)
// =========================================================================
Route::prefix('data_master')->name('data_master.')->group(function () {

    // 6.1. Laporan & Transaksi Harian Gudang
    Route::controller(ReportDailyController::class)->group(function () {
        Route::get('/report-daily', 'index')->name('report');
        Route::get('/calendar-status', 'getCalendarStatus')->name('calendar_status');
        Route::get('/daily-data', 'getDailyData')->name('daily_data');
        Route::post('/daily-upload', 'uploadDaily')->name('daily_upload');
        Route::get('/summary-data', 'getSummaryData')->name('summary_data');
        Route::get('/check-missing-master', 'checkMissingMaster')->name('check_missing');
    });

    // 6.2. Manajemen Master Item Records
    Route::controller(MasterItemController::class)->group(function () {
        Route::get('/master-items', 'index')->name('master_items');
        Route::post('/master-store', 'store')->name('master_store');
        Route::post('/master-update/{id}', 'update')->name('master_update');
        Route::delete('/master-delete/{id}', 'destroy')->name('master_delete');
    });

    // 6.3. Monitoring Kalender Validasi Barcode
    Route::controller(UploadStockBarcodeController::class)->group(function () {
        Route::get('/monitoring-list', 'list')->name('monitoring_list');
        Route::post('/monitoring-upload', 'upload')->name('monitoring_upload');
        Route::get('/monitoring-calendar', 'getCalendarBC')->name('monitoring_calendar');
    });
});
