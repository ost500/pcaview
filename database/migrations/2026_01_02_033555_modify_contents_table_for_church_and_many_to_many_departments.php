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
        // 1. Add church_id to contents table
        Schema::table('contents', function (Blueprint $table) {
            $table->foreignId('church_id')->nullable()->after('id')->constrained()->nullOnDelete();
            // Make department_id nullable since we'll use pivot table
            $table->unsignedBigInteger('department_id')->nullable()->change();
        });

        // 2. Create pivot table for contents-departments many-to-many relationship
        Schema::create('content_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['content_id', 'department_id']);
        });

        // 3. Migrate existing data: create pivot entries for existing contents
        DB::statement('
            INSERT INTO content_department (content_id, department_id, created_at, updated_at)
            SELECT id, department_id, created_at, updated_at
            FROM contents
            WHERE department_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_department');

        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['church_id']);
            $table->dropColumn('church_id');
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
        });
    }
};
