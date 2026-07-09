<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot each hole's par (from the chosen course/teebox) onto the game so
     * the scorecard can show PAR per hole and colour scores by result. Nullable
     * for games created before this (they fall back to a neutral pad).
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->json('hole_pars')->nullable()->after('par');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('hole_pars');
        });
    }
};
