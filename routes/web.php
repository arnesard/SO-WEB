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
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikProgressSOController;
use App\Http\Controllers\dashboard\oracle_vs_fisik\OracleVsFisikNonBarcodeTagStockController;
use App\Http\Controllers\appkso\SnapShotController;
use App\Http\Controllers\appkso\MasterItemController as AppksoMasterItemController;
use App\Http\Controllers\appkso\MasterPicController;
use App\Http\Controllers\appkso\BarcodeMonStockController;
use App\Http\Controllers\appkso\TagStockController;
use App\Http\Controllers\appkso\NonBarcode;
use App\Http\Controllers\so_karantina\SoKarantinaController;
use App\Http\Controllers\so_karantina\UploadBstbController;
use App\Http\Controllers\so_karantina\SpesialEntriKarantinaController;
use App\Http\Controllers\so_karantina\RevisiKsoKarantinaController;
use App\Http\Controllers\so_karantina\ValidasiKsoKarantinaController;

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
            Route::post('/truncate-all', 'truncateAll')->name('truncate-all');
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
            Route::post('/validasi-appkso', 'validateAppkso')->name('validasi-appkso')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/cek-doc', 'checkDoc')->name('cek-doc')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/scan-history', 'getScanHistory')->name('scan-history')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::get('/get-so-names-cntso', 'getSoNamesCntso')->name('get-so-names-cntso');
        });

    // 3.5.1. Modul Tag Stock NON Barcode
    Route::controller(OracleVsFisikNonBarcodeTagStockController::class) // <-- PAKAI NAMA CLASS BARU
        ->prefix('tagstock-nonbarcode') // <-- Diubah menggunakan strip (-) agar sinkron dengan file JS & Blade
        ->group(function () {
            Route::post('/upload', 'uploadExcel')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::get('/init-filters', 'initFilters');
            Route::get('/operators', 'getOperatorsByWarehouse');
            Route::post('/process-rows', 'processRows')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/update-actual', 'updateActualQty')
                ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::match(['get', 'post'], '/print', 'printTagStock')->name('print');
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
    // === ROUTE CORE APPKSO SCAN ===
    Route::get('/', [Appkso::class, 'index'])->name('appkso.index');
    Route::post('/fetch-tag-data', [Appkso::class, 'fetchTagKSOData'])->name('appkso.fetch-tag-data');
    Route::post('/print-preview', [Appkso::class, 'generatePrintPreview'])->name('appkso.print-preview');
    Route::get('/rekap-kso', [Appkso::class, 'rekapKso'])->name('rekap.kso');
    Route::post('/print-rekap-preview', [Appkso::class, 'generateRekapPreview'])->name('appkso.print-rekap-preview');
    Route::post('/save-session-date', [Appkso::class, 'saveSessionDate'])->name('save.session.date');

    // === ROUTE MASTER ITEM (Harus mandiri karena data_master tidak punya output JSON) ===
    Route::get('/master-item', [AppksoMasterItemController::class, 'index'])->name('appkso.master_item.index');
    Route::get('/master-item/data', [AppksoMasterItemController::class, 'getData'])->name('appkso.master_item.data');
    Route::post('/master-store', [AppksoMasterItemController::class, 'store']);
    Route::post('/master-update/{id}', [AppksoMasterItemController::class, 'update']);
    Route::delete('/master-delete/{id}', [AppksoMasterItemController::class, 'destroy']);

    // ★ ROUTE BARU: Item Code List untuk autocomplete
    Route::get('/master-item/itemcode-list', [AppksoMasterItemController::class, 'getItemCodeList'])
        ->name('appkso.master_item.itemcode_list');

    // ★ ROUTE BARU: Master Item Similar (CRUD)
    Route::get('/similar-item/data', [AppksoMasterItemController::class, 'getSimilarData'])
        ->name('appkso.similar_item.data');
    Route::post('/similar-store', [AppksoMasterItemController::class, 'storeSimilar']);
    Route::post('/similar-update/{id}', [AppksoMasterItemController::class, 'updateSimilar']);
    Route::delete('/similar-delete/{id}', [AppksoMasterItemController::class, 'destroySimilar'])
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    // === ROUTE HALAMAN VIEW (Data dikelola oleh modul OracleVsFisik / Data Master) ===
    Route::get('/master-pic', [MasterPicController::class, 'index'])->name('appkso.master_pic.index');
    Route::get('/barcode-mon-stock', [BarcodeMonStockController::class, 'index'])->name('appkso.barcode_mon_stock.index');

    // 👈 TAMBAHKAN BARIS INI UNTUK NON BARCODE
    Route::get('/non-barcode', [NonBarcode::class, 'index'])->name('appkso.non_barcode.index');

    // ROUTE BARU UNTUK EXPORT BA
    Route::get('/export-ba-data', [Appkso::class, 'getExportBaData'])->name('appkso.export_ba_data');

    Route::get('/tag-stock', [TagStockController::class, 'index'])->name('appkso.tag_stock.index');

    Route::post('/test-validasi', [TagStockController::class, 'testValidasi']);

    Route::controller(TagStockController::class)
        ->prefix('tag-stock')
        ->name('appkso.tag_stock.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/init-filters', 'initFilters')->name('init-filters');
            Route::get('/operators', 'getOperatorsByWarehouse')->name('operators');
            Route::post('/process-rows', 'processRows')->name('process-rows')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/validasi-appkso', 'validateAppkso')->name('validasi-appkso')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/cek-doc', 'checkDoc')->name('cek-doc')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::post('/scan-history', 'getScanHistory')->name('scan-history')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
            Route::get('/get-so-names-cntso', 'getSoNamesCntso')->name('get-so-names-cntso');
            Route::match(['get', 'post'], '/print', 'printTagStock')->name('print');
            Route::post('/test-validasi', 'testValidasi')->name('test-validasi');
            Route::post('/validasi-appkso', 'validateAppkso')->name('validasi-appkso');
        });
});

// =========================================================================
// DASHBOARD APPKSO GLOBAL (NEW - DARI CNTSO & SNAPSHOT BPW)
// =========================================================================
Route::prefix('dashboard-so')
    ->name('dashboard_so_auto.')
    ->controller(\App\Http\Controllers\appkso\DashboardSoAutoController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/get-comparison', 'getComparisonData')->name('comparison');
        Route::get('/get-detail-pattern', 'getDetailPattern')->name('detail-pattern');
        Route::get('/get-detail-ppm', 'getDetailPPM')->name('detail-ppm');
        Route::get('/get-unscanned-items', 'getUnscannedItems')->name('unscanned-items');
        Route::get('/get-scan-history', 'getScanHistory')->name('scan-history');
        Route::get('/get-detail-price-pattern', 'getDetailPricePattern')->name('detail-price-pattern');
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
    ->controller(OracleVsFisikProgressSOController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/detail', 'getDetail')->name('detail');
    });

// =========================================================================
// AUTO PROGRESS STOCK OPNAME (sumber data: fginvc.cntso)
// =========================================================================
Route::prefix('auto-progress')
    ->name('auto_progress.')
    ->controller(\App\Http\Controllers\appkso\AutoProgressSOController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/detail', 'getDetail')->name('detail');
    });
// =========================================================================
// SNAPSHOT BPW
// =========================================================================
Route::prefix('appkso/snapshot')->name('appkso.snapshot.')->group(function () {
    Route::get('/',             [SnapShotController::class, 'index'])->name('index');        // <-- halaman view baru
    Route::get('/get-so-names', [SnapShotController::class, 'getSoNames'])->name('getSoNames');
    Route::get('/data',         [SnapShotController::class, 'getData'])->name('getData');
    Route::post('/import',      [SnapShotController::class, 'import'])->name('import')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
});


Route::get('/appkso/generate-xlsm', [Appkso::class, 'generateUploadOracleXlsm'])->name('appkso.generate-xlsm');
Route::get('/appkso/open-folder', [Appkso::class, 'openSharedFolder'])->name('appkso.open-folder');
Route::post('/save-session-date', [Appkso::class, 'saveSessionDate'])
    ->name('save.session.date');

// Tambahkan ini (baru):
Route::post('/save-session-date-posisi', [Appkso::class, 'saveSessionDatePosisi'])
    ->name('save.session.date.posisi');

Route::controller(SoKarantinaController::class)
    ->prefix('so-karantina')
    ->name('so_karantina.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/data', 'getData')->name('data');
        Route::get('/scan-detail', 'getScanDetail')->name('scan_detail');
    });

//  grup so_karantina
Route::controller(UploadBstbController::class)
    ->prefix('so-karantina/upload-bstb')
    ->name('so_karantina.upload_bstb.')
    ->group(function () {
        Route::post('/', 'upload')->name('upload');
    });

// =========================================================================
// MODUL SO KARANTINA (HANDHELD / SCANNER MENU)
// =========================================================================
Route::prefix('so-karantina')->group(function () {

    Route::get('/pilih-menu', function () {
        // Ganti 'dashboard.index' ini ke rute Dashboard utama lu kalau beda
        return redirect()->route('dashboard.index');
    })->name('pilihmenu.index');

    // Menu Utama Handheld
    Route::get('/menu', function () {
        return view('so_karantina.menu');
    })->name('so_karantina.menu');

    // 1. Spesial Entry
    Route::controller(SpesialEntriKarantinaController::class)
        ->prefix('spesial')
        ->name('karantina.spesial.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/set-team', 'setTeam')->name('setTeam');
            Route::post('/store', 'store')->name('store');
            Route::post('/reset-team', 'resetTeam')->name('resetTeam');
        });

    // 2. Revisi KSO
    Route::controller(RevisiKsoKarantinaController::class)
        ->prefix('revisi')
        ->name('karantina.revisi.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/set-filter', 'setFilter')->name('setFilter');
            Route::post('/update', 'update')->name('update');
            Route::post('/reset-team', 'resetTeam')->name('resetTeam');
        });

    // 3. Validasi KSO
    Route::controller(ValidasiKsoKarantinaController::class)
        ->prefix('validasi')
        ->name('karantina.validasi.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/set-team', 'setTeam')->name('setTeam');
            Route::post('/set-nodoc', 'setNoDoc')->name('setNoDoc');
            Route::post('/reset-team', 'resetTeam')->name('resetTeam');
            Route::post('/reset-nodoc', 'resetNoDoc')->name('resetNoDoc');
            Route::post('/approve/{id}', 'approve')->name('approve');
            Route::post('/reject/{id}', 'reject')->name('reject');
        });
});
