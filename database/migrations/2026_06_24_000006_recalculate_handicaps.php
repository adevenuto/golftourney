<?php

use App\Models\League;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recompute every member's per-league handicap from their (now correctly
     * re-keyed) rounds, rather than trusting the handicap values copied over
     * from golfer_league — those were stale/out of sync with the rounds in the
     * source data. This makes league_user.handicap correct-by-construction,
     * matching exactly what a round-save recalculation produces.
     *
     * No-op on a fresh/empty DB (e.g. tests), which has no memberships yet.
     */
    public function up(): void
    {
        $handicaps = app(HandicapService::class);

        DB::table('league_user')->orderBy('id')->each(function ($row) use ($handicaps) {
            $user = User::find($row->user_id);
            $league = League::find($row->league_id);

            if ($user && $league) {
                $handicaps->recalculateFor($user, $league);
            }
        });
    }

    public function down(): void
    {
        // Forward-only: the prior (stale) handicap values are not restored.
    }
};
