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
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikSnapshotController;
use App\Http\Controllers\dashboard\ProgressController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikNonBarcodeTagStockController;
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
    Route::get('/oracle-fisik/get-comparison', 'getComparisonData')->name('oracle_fisik.comparison');
    Route::get('/oracle-fisik/get-detail-pattern', 'getDetailPattern')->name('oracle_fisik.detail-pattern');
    Route::get('/oracle-fisik/get-scan-history', 'getScanHistory')->name('oracle_fisik.scan-history');
    Route::get('/oracle-fisik/get-unscanned-items', 'getUnscannedItems')->name('oracle_fisik.unscanned-items');
    Route::get('/oracle-fisik/get-detail-price-pattern', [OracleVsFisikController::class, 'getDetailPricePattern']);
    Route::get('/oracle-fisik/get-detail-grade', [OracleVsFisikController::class, 'getDetailGrade']);
    Route::get('/oracle-fisik/get-detail-ppm', [OracleVsFisikController::class, 'getDetailPPM']);
});


// =========================================================================
// 3. ROUTE KHUSUS SUB-MODUL INTERNAL FISIK (PREFIXED & RE-GROUPED)
// =========================================================================
Route::prefix('oracle-fisik')->name('oracle_fisik.')->group(function () {

    // 3.1. Master Size Area
    Route::controller(OracleVsFisikMasterSizeController::class)
        ->prefix('master-size')
        ->name('master_size.')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class) // Terapkan ke semua di grup ini
        ->group(function () {
            Route::get('/data', 'getData')->name('data');
            Route::post('/store', 'store')->name('store');
            Route::post('/update/{id}', 'update')->name('update');
            Route::delete('/delete/{id}', 'destroy')->name('delete');
        });

    // 3.2. Barcode Monitoring Stock Area
    Route::controller(OracleVsFisikBarcodeMonstockController::class)
        ->prefix('barcode')
        ->name('barcode.')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->group(function () {
            Route::get('/data', 'getData')->name('data');
            Route::post('/import', 'import')->name('import');
            Route::delete('/delete/{id}', 'destroy')->name('delete');
        });

    // 3.3. Master PIC & Auditor Area
    Route::controller(OracleVsFisikPicController::class)
        ->prefix('pic')
        ->name('pic.')
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->group(function () {
            Route::get('/data', 'getData')->name('data');
            Route::post('/store', 'storeOrUpdate')->name('store');
            Route::delete('/delete/{id}', 'destroy')->name('delete');
        });

    // 3.4. APPKSO Import Area
    Route::controller(OracleVsFisikAppksoController::class)
        ->prefix('appkso')
        ->name('appkso.')
        ->group(function () {
            Route::post('/import', 'import')->name('import');
            Route::get('/data', 'getData')->name('data');
            Route::get('/get-warehouses', 'getWarehouseList')->name('get-warehouses');
            Route::get('/documents', [OracleVsFisikTagStockController::class, 'getDocuments']); // Ini nyelip controller lain, tapi ok
        });

    // 3.5. Modul Cetak Tag Stock Mandiri (Disatukan, tidak dipisah 2 grup lagi)
    Route::controller(OracleVsFisikTagStockController::class)
        ->prefix('tagstock')
        ->name('tagstock.')
        ->group(function () {
            Route::match(['get', 'post'], '/print', 'printTagStock')->name('print');
            Route::get('/init-filters', 'initFilters')->name('init-filters');
            Route::get('/operators', 'getOperatorsByWarehouse')->name('operators');
            Route::get('/operator-details', 'getOperatorDetails')->name('operator-details');
            Route::post('/process-rows', 'processRows')->name('process-rows')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        });

    // 3.5.1. Modul Tag Stock NON Barcode
    Route::controller(OracleVsFisikNonBarcodeTagStockController::class) // <-- PAKAI NAMA CLASS BARU
        ->prefix('tagstocknonbarcode') // <-- Buat tanpa strip agar sinkron dengan file JS asli lu
        ->group(function () {
            Route::post('/upload', 'uploadExcel')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::get('/init-filters', 'initFilters');
            Route::get('/operators', 'getOperatorsByWarehouse');
            Route::post('/process-rows', 'processRows')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        });

    // 3.6. Modul Snapshot Oracle
    Route::controller(OracleVsFisikSnapshotController::class)
        ->prefix('snapshot')
        ->name('snapshot.')
        ->group(function () {
            Route::get('/get-warehouses', 'getWarehouseList')->name('get-warehouses');
            Route::get('/data', 'getData')->name('data');
            Route::post('/import', 'importExcel')->name('import')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        });
});


// =========================================================================
// 4. MODUL SCAN APPKSO UTAMA
// =========================================================================
Route::prefix('appkso')->group(function () {
    Route::get('/', [Appkso::class, 'index'])->name('appkso.index');
    Route::post('/fetch-tag-data', [Appkso::class, 'fetchTagKSOData'])->name('appkso.fetch-tag-data');
    Route::post('/print-preview', [Appkso::class, 'generatePrintPreview'])->name('appkso.print-preview');
    Route::get('/rekap-kso', [Appkso::class, 'rekapKso'])->name('rekap.kso');
    Route::post('/print-rekap-preview', [Appkso::class, 'generateRekapPreview'])->name('appkso.print-rekap-preview');
    Route::post('/save-session-date', [Appkso::class, 'saveSessionDate'])->name('save.session.date');
});



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

// =========================================================================
// LIVE PROGRESS STOCK OPNAME
// =========================================================================
Route::prefix('progress')
    ->name('progress.')
    ->controller(ProgressController::class)
    ->group(function () {

        Route::get('/', 'index')->name('index');
    });
