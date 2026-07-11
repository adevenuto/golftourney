<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot each hole's length (yardage) from the chosen course/teebox onto
     * the game, so the scorecard can show a yardage row. Nullable for games
     * created before this (no yardage row shown).
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->json('hole_lengths')->nullable()->after('hole_pars');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('hole_lengths');
        });
    }
};
