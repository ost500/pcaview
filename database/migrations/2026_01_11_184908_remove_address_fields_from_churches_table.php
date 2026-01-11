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
            if (Schema::hasColumn('churches', 'address_url')) {
                $table->dropColumn('address_url');
            }
            if (Schema::hasColumn('churches', 'address_image')) {
                $table->dropColumn('address_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->string('address_url')->nullable();
            $table->string('address_image')->nullable();
        });
    }
};
