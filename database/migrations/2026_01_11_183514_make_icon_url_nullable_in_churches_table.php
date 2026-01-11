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
        Schema::table('churches', function (Blueprint $table) {
            $table->string('icon_url')->nullable()->change();
            $table->string('logo_url')->nullable()->change();
            $table->string('address_url')->nullable()->change();
            $table->string('worship_time_image')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('icon_url')->nullable(false)->change();
            $table->string('logo_url')->nullable(false)->change();
            $table->string('address_url')->nullable(false)->change();
            $table->string('worship_time_image')->nullable(false)->change();
        });
    }
};
