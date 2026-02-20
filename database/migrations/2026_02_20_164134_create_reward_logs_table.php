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
        Schema::create('reward_logs', function (Blueprint $table) {
            $table->id();
            $table->string('encrypted'); // 암호화된 사용자 식별 정보
            $table->string('reward_type'); // 리워드 종류 (watch, ad 등)
            $table->string('where')->nullable(); // 리워드 발생 위치/출처
            $table->text('video_url')->nullable(); // 시청한 비디오 URL
            $table->integer('video_time')->nullable(); // 비디오 시청 시간 (초)
            $table->string('video_stringtime')->nullable(); // 비디오 시청 시간 (문자열)
            $table->integer('points_earned')->default(0); // 적립된 포인트
            $table->timestamps();

            $table->index(['encrypted', 'reward_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_logs');
    }
};
