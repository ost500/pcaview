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
        Schema::create('install_counts', function (Blueprint $table) {
            $table->id();
            $table->text('referrer')->nullable(); // 설치 레퍼러 정보
            $table->string('user_agent')->nullable(); // 사용자 에이전트
            $table->string('ip_address', 45)->nullable(); // IP 주소 (IPv6 지원)
            $table->timestamps();

            $table->index('created_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('install_counts');
    }
};
