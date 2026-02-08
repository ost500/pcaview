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
        Schema::create('park_golf_courses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('region', 50)->nullable()->index();
            $table->string('address')->nullable();
            $table->string('area')->nullable();
            $table->integer('holes')->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->string('detail_url')->nullable();
            $table->timestamps();

            // 좌표 기반 검색을 위한 복합 인덱스
            $table->index(['longitude', 'latitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_golf_courses');
    }
};
