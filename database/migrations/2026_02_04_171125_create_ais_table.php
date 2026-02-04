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
        Schema::create('ais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ai_id')->nullable()->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('created')->nullable();
            $table->string('question_role')->default('user');
            $table->text('question');
            $table->string('answer_role')->nullable();
            $table->text('answer')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ais');
    }
};
