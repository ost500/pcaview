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
        Schema::create('reward_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_reward_id')->constrained()->onDelete('cascade');
            $table->foreignId('reward_id')->constrained()->onDelete('cascade');
            $table->integer('points_spent'); // 사용한 포인트
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['user_reward_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_usages');
    }
};
