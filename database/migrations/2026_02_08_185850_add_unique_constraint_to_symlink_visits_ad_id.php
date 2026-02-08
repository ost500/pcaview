<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 중복된 ad_id 정리 (가장 최근 것만 남기고 삭제)
        DB::statement('
            DELETE t1 FROM symlink_visits t1
            INNER JOIN symlink_visits t2
            WHERE t1.id < t2.id
            AND t1.ad_id = t2.ad_id
            AND t1.ad_id IS NOT NULL
        ');

        // 기존 인덱스가 있으면 제거
        try {
            Schema::table('symlink_visits', function (Blueprint $table) {
                $table->dropIndex(['ad_id']);
            });
        } catch (\Exception $e) {
            // 인덱스가 없으면 무시
        }

        // unique 제약 조건 추가
        Schema::table('symlink_visits', function (Blueprint $table) {
            $table->unique('ad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('symlink_visits', function (Blueprint $table) {
            // unique 제약 조건 제거하고 일반 인덱스로 복원
            $table->dropUnique(['ad_id']);
            $table->index('ad_id');
        });
    }
};
