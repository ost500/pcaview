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
        Schema::dropIfExists('user_rewards');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // user_rewards 테이블은 더 이상 사용하지 않으므로 복구하지 않음
        // reward_balances 테이블을 사용
    }
};
