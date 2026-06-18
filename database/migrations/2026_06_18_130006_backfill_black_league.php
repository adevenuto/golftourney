<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fold the existing single-tenant data into "The Black League".
     * Guarded so it no-ops on an empty database (e.g. SQLite test runs).
     */
    public function up(): void
    {
        $owner = DB::table('users')->where('role', 'admin')->orderBy('id')->first()
            ?? DB::table('users')->orderBy('id')->first();

        if (! $owner) {
            return; // fresh/empty DB — nothing to backfill
        }

        $now = Carbon::now();

        $leagueId = DB::table('leagues')->insertGetId([
            'name' => 'The Black League',
            'owner_id' => $owner->id,
            'course_rating' => 31.5,
            'slope_rating' => 104,
            'recent_rounds' => 20,
            'counting_rounds' => 8,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Golfers -> league, moving each handicap onto the pivot.
        DB::table('golfers')->orderBy('id')->select('id', 'handicap')->chunk(500, function ($golfers) use ($leagueId, $now) {
            DB::table('golfer_league')->insert(
                $golfers->map(fn ($g) => [
                    'golfer_id' => $g->id,
                    'league_id' => $leagueId,
                    'handicap' => $g->handicap ?? 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        // Stamp every existing round with the league.
        DB::table('rounds')->update(['league_id' => $leagueId]);

        // Per-league roles from the global role; set everyone's current league.
        DB::table('users')->orderBy('id')->select('id', 'role')->chunk(500, function ($users) use ($leagueId, $now) {
            DB::table('league_user')->insert(
                $users->map(fn ($u) => [
                    'user_id' => $u->id,
                    'league_id' => $leagueId,
                    'role' => $u->role ?? 'player',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        DB::table('users')->update(['current_league_id' => $leagueId]);
    }

    public function down(): void
    {
        DB::table('users')->update(['current_league_id' => null]);
        DB::table('rounds')->update(['league_id' => null]);
        DB::table('league_user')->delete();
        DB::table('golfer_league')->delete();
        DB::table('leagues')->delete();
    }
};
