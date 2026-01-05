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
        Schema::create('gold_prices', function (Blueprint $table) {
            $table->id();
            $table->dateTime('price_date')->unique(); // 시세 업데이트 시간

            // 순금 (99.99%)
            $table->integer('p_pure'); // 매입가
            $table->integer('s_pure'); // 판매가

            // 18K 금 (75%)
            $table->integer('p_18k'); // 매입가
            $table->integer('s_18k'); // 판매가

            // 14K 금 (58.5%)
            $table->integer('p_14k'); // 매입가
            $table->integer('s_14k'); // 판매가

            // 백금
            $table->integer('p_white')->nullable(); // 매입가
            $table->integer('s_white')->nullable(); // 판매가

            // 은
            $table->integer('p_silver')->nullable(); // 매입가
            $table->integer('s_silver')->nullable(); // 판매가

            $table->timestamps();

            $table->index('price_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gold_prices');
    }
};
