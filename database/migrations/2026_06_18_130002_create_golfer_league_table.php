<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golfer_league', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->decimal('handicap', 4, 2)->default(0); // per-league handicap
            $table->timestamps();

            $table->unique(['golfer_id', 'league_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golfer_league');
    }
};
