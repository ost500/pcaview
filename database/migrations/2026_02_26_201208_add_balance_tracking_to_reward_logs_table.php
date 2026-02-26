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
            $table->decimal('before_balance', 18, 9)->nullable()->after('points_earned')->comment('적립 전 잔액');
            $table->decimal('after_balance', 18, 9)->nullable()->after('before_balance')->comment('적립 후 잔액');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->dropColumn(['before_balance', 'after_balance']);
        });
    }
};
