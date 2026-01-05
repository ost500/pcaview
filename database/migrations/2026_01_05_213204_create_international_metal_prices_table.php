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
        Schema::create('international_metal_prices', function (Blueprint $table) {
            $table->id();
            $table->dateTime('price_date')->unique();

            // 금 스팟 가격 (USD per troy ounce)
            $table->decimal('gold_usd', 10, 2);

            // 은 스팟 가격
            $table->decimal('silver_usd', 10, 2);

            // 백금 스팟 가격
            $table->decimal('platinum_usd', 10, 2)->nullable();

            // 팔라듐 (나중에 추가 가능)
            $table->decimal('palladium_usd', 10, 2)->nullable();

            $table->timestamps();

            $table->index('price_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('international_metal_prices');
    }
};
