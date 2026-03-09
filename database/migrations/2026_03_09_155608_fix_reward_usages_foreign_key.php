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
            // 기존 잘못된 foreign key 삭제
            $table->dropForeign(['user_reward_id']);

            // 올바른 테이블을 참조하는 foreign key 추가
            $table->foreign('user_reward_id')
                ->references('id')
                ->on('reward_balances')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_usages', function (Blueprint $table) {
            $table->dropForeign(['user_reward_id']);

            // 원래대로 복구 (잘못된 참조지만 롤백용)
            $table->foreign('user_reward_id')
                ->references('id')
                ->on('user_rewards')
                ->onDelete('cascade');
        });
    }
};
