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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // 앱 이름 (예: GOLDNITY, YTPlayer)
            $table->string('code')->unique(); // 앱 코드 (예: goldnity, ytplayer)
            $table->text('description')->nullable(); // 앱 설명
            $table->boolean('is_active')->default(true); // 활성화 여부
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
