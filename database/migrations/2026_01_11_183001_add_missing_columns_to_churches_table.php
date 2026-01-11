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
            // icon_image가 없다면 추가 (icon_url과 별도로)
            if (!Schema::hasColumn('churches', 'icon_image')) {
                $table->string('icon_image')->nullable()->after('primary_department_id');
            }

            // logo_image 추가
            if (!Schema::hasColumn('churches', 'logo_image')) {
                $table->string('logo_image')->nullable()->after('icon_image');
            }

            // address_image 추가
            if (!Schema::hasColumn('churches', 'address_image')) {
                $table->string('address_image')->nullable()->after('address_url');
            }

            // description이 없다면 추가
            if (!Schema::hasColumn('churches', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table) {
            $table->dropColumn(['icon_image', 'logo_image', 'address_image', 'description']);
        });
    }
};
