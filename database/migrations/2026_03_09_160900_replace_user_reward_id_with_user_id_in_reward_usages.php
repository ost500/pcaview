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
            // user_reward_id foreign key 삭제
            $table->dropForeign(['user_reward_id']);
            $table->dropColumn('user_reward_id');

            // user_id 추가
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_usages', function (Blueprint $table) {
            // user_id 삭제
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');

            // user_reward_id 복구
            $table->foreignId('user_reward_id')->after('id')->constrained('reward_balances')->onDelete('cascade');
            $table->index(['user_reward_id', 'status']);
        });
    }
};
