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
        Schema::table('trends', function (Blueprint $table) {
            // department_id와 pub_date 복합 인덱스 추가
            // pub_date는 DESC 정렬로 자주 사용되므로 복합 인덱스로 성능 향상
            $table->index(['department_id', 'pub_date'], 'idx_department_pub_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trends', function (Blueprint $table) {
            $table->dropIndex('idx_department_pub_date');
        });
    }
};
