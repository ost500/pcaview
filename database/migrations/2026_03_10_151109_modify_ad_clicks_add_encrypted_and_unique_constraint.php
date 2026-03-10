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
        Schema::table('ad_clicks', function (Blueprint $table) {
            // encrypted 컬럼 추가
            $table->string('encrypted', 500)->nullable()->after('redirect_url');

            // 기존 ad_id unique 제거
            $table->dropUnique(['ad_id']);

            // ad_id, encrypted 복합 unique 추가
            $table->unique(['ad_id', 'encrypted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_clicks', function (Blueprint $table) {
            // 복합 unique 제거
            $table->dropUnique(['ad_id', 'encrypted']);

            // ad_id unique 복원
            $table->unique('ad_id');

            // encrypted 컬럼 제거
            $table->dropColumn('encrypted');
        });
    }
};
