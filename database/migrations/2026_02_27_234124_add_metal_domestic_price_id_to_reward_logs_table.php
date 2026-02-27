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
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->foreignId('metal_domestic_price_id')
                ->nullable()
                ->after('after_balance')
                ->comment('적립 당시 금 시세 ID')
                ->constrained('metal_domestic_prices')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->dropForeign(['metal_domestic_price_id']);
            $table->dropColumn('metal_domestic_price_id');
        });
    }
};
