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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version'); // e.g., "1.0.0"
            $table->enum('platform', ['ios', 'android', 'all'])->default('all');
            $table->boolean('is_force_update')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('update_url')->nullable();
            $table->text('update_message')->nullable();
            $table->timestamps();

            $table->index(['platform', 'is_active']);
            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
