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
        Schema::create('reward_balances', function (Blueprint $table) {
            $table->id();
            $table->string('encrypted')->unique(); // 암호화된 사용자 식별자
            $table->integer('balance')->default(0); // 현재 포인트 잔액
            $table->integer('total_earned')->default(0); // 총 적립 포인트
            $table->integer('total_spent')->default(0); // 총 사용 포인트
            $table->timestamps();

            $table->index('encrypted');
            $table->index('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_balances');
    }
};
