<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who is in a game. `confirmed_at` is an attestation-ready hook (mutual
     * sign-off is a later feature — no UI yet). `round_id` is set at finalize,
     * pointing at the casual round created for the player (finalize idempotency).
     */
    public function up(): void
    {
        Schema::create('game_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('round_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['game_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_players');
    }
};
