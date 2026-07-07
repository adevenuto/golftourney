<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (game, player, hole). Unique on that triple so a score entry
     * is a simple upsert; the gross total rolls up from these.
     */
    public function up(): void
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('hole');
            $table->unsignedTinyInteger('strokes')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'user_id', 'hole']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
