<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('worship_time_image')->after('logo_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn('worship_time_image');
        });
    }
};
