<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the golfer tables now that golfers live in users and their roster
     * membership/handicap lives on league_user. golfer_league is dropped first
     * (it FKs golfers). Forward-only — down() recreates the empty shells only.
     */
    public function up(): void
    {
        Schema::dropIfExists('golfer_league');
        Schema::dropIfExists('golfers');
    }

    public function down(): void
    {
        Schema::create('golfers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('golfer_league', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->decimal('handicap', 4, 2)->default(0);
            $table->timestamps();
            $table->unique(['golfer_id', 'league_id']);
        });
    }
};
