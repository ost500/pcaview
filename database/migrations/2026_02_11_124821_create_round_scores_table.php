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
        Schema::create('round_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_player_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('hole_number');
            $table->tinyInteger('par');
            $table->tinyInteger('score')->nullable();
            $table->string('memo', 255)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'round_player_id', 'hole_number'], 'round_score_unique');
            $table->index('round_player_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_scores');
    }
};
