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
            $table->decimal('points_value', 30, 15)->nullable()->change();
            $table->decimal('after_balance_value', 30, 15)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_logs', function (Blueprint $table) {
            $table->decimal('points_value', 18, 9)->nullable()->change();
            $table->decimal('after_balance_value', 18, 9)->nullable()->change();
        });
    }
};
