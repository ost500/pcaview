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
        Schema::create('contents_platform_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->string('comment_id')->unique(); // 'naver_880954130233819315' 형태의 고유 ID
            $table->string('source'); // 'naver', 'kakao', 'youtube' etc.
            $table->string('author'); // 'nl17****'
            $table->text('content'); // 댓글 내용
            $table->integer('likes')->default(0); // 좋아요 수
            $table->integer('dislikes')->default(0); // 싫어요 수
            $table->string('created_date')->nullable(); // '12.31 08:27' 형태의 원본 날짜
            $table->boolean('is_best')->default(false); // 베스트 댓글 여부
            $table->boolean('is_mobile')->default(false); // 모바일 작성 여부
            $table->integer('reply_count')->default(0); // 답글 수
            $table->timestamps();

            $table->index(['content_id', 'created_at']);
            $table->index(['source', 'comment_id']);
            $table->index('is_best');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents_platform_comments');
    }
};
