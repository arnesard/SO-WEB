    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\appkso\Appkso;
    use App\Http\Controllers\stock_barcode\StockBarcode;
    use App\Http\Controllers\data_master\ReportDailyController;
    use App\Http\Controllers\data_master\MasterItemController;
    use App\Http\Controllers\data_master\UploadStockBarcodeController;
    use App\Http\Controllers\dashboard\OracleVsBarcodeController;


    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    */

    // routes/web.php

    Route::get('/', [OracleVsBarcodeController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard', [OracleVsBarcodeController::class, 'index']);
    Route::get('/oracle-barcode', [OracleVsBarcodeController::class, 'index'])->name('oracle_barcode.index');
    Route::get('/oracle-barcode/chart-data', [OracleVsBarcodeController::class, 'getChartData'])->name('oracle_barcode.chart');
    Route::get('/oracle-barcode/detail-pattern', [OracleVsBarcodeController::class, 'getDetailByPattern'])->name('oracle-barcode.detail');
    Route::get('/oracle-barcode/deep-detail', [OracleVsBarcodeController::class, 'getDeepDetailBarcode'])->name('oracle-barcode.deep-detail');

    Route::get('/appkso', [Appkso::class, 'index'])->name('appkso.index');
    Route::get('/stock-barcode', [StockBarcode::class, 'index'])->name('stock.barcode');

    // Grouping untuk data_master
    Route::prefix('data_master')->name('data_master.')->group(function () {

        // Rute Laporan & Transaksi (Rumah Baru)
        Route::controller(ReportDailyController::class)->group(function () {
            // Ini yang dipanggil route('data_master.report')
            Route::get('/report-daily', 'index')->name('report');
            Route::get('/calendar-status', 'getCalendarStatus')->name('calendar_status');
            Route::get('/daily-data', 'getDailyData')->name('daily_data');
            Route::post('/daily-upload', 'uploadDaily')->name('daily_upload');
            Route::get('/summary-data', 'getSummaryData')->name('summary_data');
            Route::get('/check-missing-master', 'checkMissingMaster')->name('check_missing');
        });

        // Rute Master Data (Rumah Baru)
        Route::controller(MasterItemController::class)->group(function () {
            Route::get('/master-items', 'index')->name('master_items');
            Route::post('/master-store', 'store')->name('master_store');
            Route::post('/master-update/{id}', 'update')->name('master_update');
            Route::delete('/master-delete/{id}', 'destroy')->name('master_delete');
        });

        Route::controller(UploadStockBarcodeController::class)->group(function () {
            Route::get('/monitoring-list', 'list')->name('monitoring_list');
            Route::post('/monitoring-upload', 'upload')->name('monitoring_upload');
            Route::get('/monitoring-calendar', 'getCalendarBC')->name('monitoring_calendar');
        });
    });
