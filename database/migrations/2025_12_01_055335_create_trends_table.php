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
        Schema::create('trends', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link');
            $table->string('image_url')->nullable();
            $table->integer('traffic_count')->nullable();
            $table->timestamp('pub_date');
            $table->timestamps();

            // 인덱스
            $table->index('pub_date');
            $table->index('traffic_count');
            $table->index('created_at');

            // 중복 방지를 위한 유니크 제약
            $table->unique(['title', 'pub_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trends');
    }
};
