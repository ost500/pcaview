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
        Schema::create('reward_products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 상품명
            $table->text('description')->nullable(); // 상품 설명
            $table->string('image_url')->nullable(); // 상품 이미지
            $table->decimal('price', 20, 9); // 가격 (포인트)
            $table->decimal('gold_grams', 20, 9)->nullable(); // 금 그램 환산
            $table->integer('stock')->default(0); // 재고 수량
            $table->boolean('is_active')->default(true); // 활성화 여부
            $table->integer('priority')->default(0); // 정렬 순서
            $table->string('category')->nullable(); // 카테고리
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_products');
    }
};
