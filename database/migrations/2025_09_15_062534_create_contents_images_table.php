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
        Schema::create('contents_images', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('contents_id')->index();
            $table->smallInteger('page');
            $table->string('file_url');
            $table->string('title')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents_images');
    }
};
