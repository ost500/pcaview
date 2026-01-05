<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 테이블 이름 변경
        Schema::rename('gold_prices', 'domestic_metal_prices');

        // 컬럼명 변경 (일관성)
        Schema::table('domestic_metal_prices', function (Blueprint $table) {
            $table->renameColumn('p_white', 'p_platinum');
            $table->renameColumn('s_white', 's_platinum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 컬럼명 원복
        Schema::table('domestic_metal_prices', function (Blueprint $table) {
            $table->renameColumn('p_platinum', 'p_white');
            $table->renameColumn('s_platinum', 's_white');
        });

        // 테이블 이름 원복
        Schema::rename('domestic_metal_prices', 'gold_prices');
    }
};
