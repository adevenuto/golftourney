<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-league handicap settings:
     *  - league_only: when true (default), this league's handicaps are derived
     *    only from rounds played IN this league (casual + other-league rounds are
     *    ignored) — the traditional "league-play only" handicap. False pools all
     *    of a player's rounds (full WHS).
     *  - display_nine_hole_index: display-only. The Index is always an 18-hole
     *    number; when true, show the 9-hole equivalent (half) wherever the Index
     *    is displayed for this league. Does not affect any calculation.
     */
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->boolean('league_only')->default(true)->after('counting_rounds');
            $table->boolean('display_nine_hole_index')->default(false)->after('league_only');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn(['league_only', 'display_nine_hole_index']);
        });
    }
};
