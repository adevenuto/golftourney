<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot each existing (league) round's course context from its league, so
     * every round is self-contained. Per-league updates (no JOIN) so it runs on
     * SQLite too. No-op on a fresh/empty DB.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('rounds', 'course_rating')) {
            return;
        }

        foreach (DB::table('leagues')->get() as $league) {
            DB::table('rounds')
                ->where('league_id', $league->id)
                ->whereNull('course_rating')
                ->update([
                    'course_id' => $league->course_id,
                    'teebox' => $league->teebox,
                    'course_rating' => $league->course_rating,
                    'slope_rating' => $league->slope_rating,
                    'par' => $league->par,
                    'holes' => $league->holes,
                ]);
        }
    }

    public function down(): void
    {
        // Forward-only: the snapshot can be re-derived from the league.
    }
};
