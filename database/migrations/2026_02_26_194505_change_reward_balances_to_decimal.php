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
        Schema::table('reward_balances', function (Blueprint $table) {
            $table->decimal('balance', 18, 9)->default(0)->change();
            $table->decimal('total_earned', 18, 9)->default(0)->change();
            $table->decimal('total_spent', 18, 9)->default(0)->change();
        });

        Schema::table('reward_logs', function (Blueprint $table) {
            $table->decimal('points_earned', 18, 9)->nullable()->change();
        });

        Schema::table('reward_usages', function (Blueprint $table) {
            $table->decimal('points_spent', 18, 9)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_balances', function (Blueprint $table) {
            $table->integer('balance')->default(0)->change();
            $table->integer('total_earned')->default(0)->change();
            $table->integer('total_spent')->default(0)->change();
        });

        Schema::table('reward_logs', function (Blueprint $table) {
            $table->integer('points_earned')->nullable()->change();
        });

        Schema::table('reward_usages', function (Blueprint $table) {
            $table->integer('points_spent')->change();
        });
    }
};
