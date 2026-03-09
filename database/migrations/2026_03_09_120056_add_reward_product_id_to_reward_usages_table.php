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
        Schema::table('reward_usages', function (Blueprint $table) {
            $table->foreignId('reward_product_id')->nullable()->after('reward_id')->constrained()->onDelete('cascade');

            // reward_id를 nullable로 변경 (reward 또는 reward_product 중 하나만 있음)
            $table->foreignId('reward_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_usages', function (Blueprint $table) {
            $table->dropForeign(['reward_product_id']);
            $table->dropColumn('reward_product_id');

            // reward_id를 다시 not null로 변경
            $table->foreignId('reward_id')->nullable(false)->change();
        });
    }
};
