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
        Schema::table('rewards', function (Blueprint $table) {
            $table->string('code')->nullable()->after('application_id')->comment('리워드 코드 (watch, ad, share, mining)');
            $table->enum('type', ['accumulation', 'exchange'])->default('exchange')->after('code')->comment('적립용 or 교환용');

            $table->index('code');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropIndex(['type']);
            $table->dropColumn(['code', 'type']);
        });
    }
};
