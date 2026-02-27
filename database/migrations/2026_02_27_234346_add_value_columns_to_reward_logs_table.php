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
            $table->decimal('points_value', 18, 9)->nullable()->after('points_earned')->comment('적립 포인트의 원화 가치 (당시 금 시세 기준)');
            $table->decimal('after_balance_value', 18, 9)->nullable()->after('after_balance')->comment('적립 후 잔액의 원화 가치 (당시 금 시세 기준)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->dropColumn(['points_value', 'after_balance_value']);
        });
    }
};
