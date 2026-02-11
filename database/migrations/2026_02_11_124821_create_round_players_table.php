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
        Schema::create('round_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('player_name', 100);
            $table->tinyInteger('player_order');
            $table->boolean('is_me')->default(false);
            $table->integer('total_score')->default(0);
            $table->integer('score_vs_par')->default(0);
            $table->tinyInteger('rank')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index('round_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_players');
    }
};
