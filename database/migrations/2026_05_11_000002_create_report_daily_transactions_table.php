<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabel Transactions
        Schema::create('report_daily_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('plant', 50)->nullable();
            $table->string('gt_type', 100)->nullable();
            $table->string('item_code', 50)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('oe_stk_awal')->default(0);
            $table->integer('oe_in')->default(0);
            $table->integer('oe_out')->default(0);
            $table->integer('oe_adj')->default(0);
            $table->integer('oe_stk_akhir')->default(0);
            $table->integer('ok_stk_awal')->default(0);
            $table->integer('ok_in')->default(0);
            $table->integer('ok_out')->default(0);
            $table->integer('ok_adj')->default(0);
            $table->integer('ok_stk_akhir')->default(0);
            $table->integer('nd_stk_awal')->default(0);
            $table->integer('nd_in')->default(0);
            $table->integer('nd_out')->default(0);
            $table->integer('nd_adj')->default(0);
            $table->integer('nd_stk_akhir')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('transaction_date', 'idx_trans_date');
            $table->index(['plant', 'gt_type'], 'idx_plant_gt');
            $table->index(['transaction_date', 'item_code'], 'idx_date_item');
        });

        // Tabel Stk Akhir
        Schema::create('report_daily_transactions_stk_akhir', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date')->nullable();
            $table->string('item_code', 100)->nullable();
            $table->string('item_code_desc', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('qty')->default(0);
            $table->timestamps();

            $table->index('transaction_date', 'idx_resume_date');
            $table->index('item_code', 'idx_resume_item');
            $table->index('item_code_desc', 'idx_resume_code_desc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_daily_transactions');
        Schema::dropIfExists('report_daily_transactions_stk_akhir');
    }
};
