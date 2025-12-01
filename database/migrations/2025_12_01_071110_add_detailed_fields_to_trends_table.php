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
            $table->string('picture')->nullable()->after('image_url');
            $table->string('picture_source')->nullable()->after('picture');
            $table->json('news_items')->nullable()->after('picture_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trends', function (Blueprint $table) {
            $table->dropColumn(['picture', 'picture_source', 'news_items']);
        });
    }
};
