<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->nullable();
            $table->string('item_code_desc', 100)->unique('idx_item_desc_unique');
            $table->string('description', 255)->nullable();
            $table->string('grade', 50)->nullable();
            $table->string('product', 100)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->timestamps();

            // Index
            $table->index('item_code', 'idx_item_code');
        });

        // Menambahkan Virtual Column Pattern (Karena Laravel Blueprint belum handle regex complex dengan concat_ws secara native)
        DB::statement("ALTER TABLE master_items ADD pattern TEXT AS (trim(regexp_replace(concat_ws(' ',if((grade = '-'),'',grade),if((product = '-'),'',product),if((type = '-'),'',type),if((brand = '-'),'',brand),if((category = '-'),'',category)),' +',' '))) VIRTUAL");
    }

    public function down(): void
    {
        Schema::dropIfExists('master_items');
    }
};
