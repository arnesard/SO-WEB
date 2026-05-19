<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Stock Barcodes
        Schema::create('stock_barcodes', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('rack_code', 50)->nullable();
            $table->string('item_code', 50)->nullable();
            $table->string('whs_week', 10)->nullable();
            $table->string('cur_week', 10)->nullable();
            $table->integer('qty')->default(0);
            $table->integer('qc')->default(0);
            $table->integer('qa')->default(0);
            $table->integer('qaa')->default(0);
            $table->integer('rnd')->default(0);
            $table->integer('holds')->default(0);
            $table->integer('oem')->default(0);
            $table->integer('ng')->default(0);
            $table->string('booking', 100)->nullable();
            $table->string('loc_code', 100)->nullable();
            $table->integer('hold_days')->default(0);
            $table->timestamps();

            $table->index('item_code', 'idx_barcode_item');
            $table->index('rack_code', 'idx_barcode_rack');
            $table->index('transaction_date', 'idx_barcode_date');
        });

        // Stock Barcodes Resume
        Schema::create('stock_barcodes_resume', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('rack_code', 50)->nullable();
            $table->string('loc_code', 100)->nullable();
            $table->string('item_code', 50)->nullable();
            $table->string('item_code_desc', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('qty')->default(0);
            $table->timestamps();

            $table->index('transaction_date', 'idx_res_date');
            $table->index('item_code_desc', 'idx_res_item_desc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_barcodes');
        Schema::dropIfExists('stock_barcodes_resume');
    }
};
