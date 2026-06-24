<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data backfill for the golfers→users merge (v5). Every golfer becomes a
     * user and their rounds + per-league handicap move onto users/league_user.
     *
     * The 4 login accounts (users 1,2,8,26) each ALSO exist as a golfer; those
     * golfers are absorbed into the existing user (no duplicate) per a
     * hand-verified map. Every other golfer becomes a new login-less user
     * (password NULL). Orphan rounds (golfer_id with no golfer) are discarded.
     *
     * Runs after the legacy-user delete so the only users present are the 4
     * login accounts; the new users it creates get fresh ids with no collision.
     * Guarded so a fresh test DB (no real golfer data) is a no-op. Forward-only:
     * golfer identity is discarded, so down() does nothing.
     */
    public function up(): void
    {
        if (! Schema::hasTable('golfers') || ! Schema::hasTable('golfer_league')) {
            return;
        }

        DB::transaction(function () {
            // login user_id => their golfer_id (hand-verified against prod data).
            $adminMap = [1 => 61, 2 => 7, 8 => 19, 26 => 64];

            // 1. Drop rounds pointing at a golfer that no longer exists.
            DB::table('rounds')
                ->whereNotIn('golfer_id', DB::table('golfers')->select('id'))
                ->delete();

            // 2. Admin merges: absorb each login user's golfer into that user.
            foreach ($adminMap as $userId => $golferId) {
                DB::table('rounds')->where('golfer_id', $golferId)
                    ->update(['user_id' => $userId]);

                foreach (DB::table('golfer_league')->where('golfer_id', $golferId)->get() as $p) {
                    $updated = DB::table('league_user')
                        ->where('user_id', $userId)
                        ->where('league_id', $p->league_id)
                        ->update(['handicap' => $p->handicap]);

                    if (! $updated) { // not yet a member of that league — add them
                        DB::table('league_user')->insert([
                            'user_id' => $userId,
                            'league_id' => $p->league_id,
                            'role' => 'player',
                            'handicap' => $p->handicap,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $phone = DB::table('golfers')->where('id', $golferId)->value('phone');
                if (! is_null($phone)) {
                    DB::table('users')->where('id', $userId)
                        ->whereNull('phone')
                        ->update(['phone' => $phone]);
                }
            }

            // 3. Every other golfer becomes a new login-less user.
            DB::table('golfers')
                ->whereNotIn('id', array_values($adminMap))
                ->orderBy('id')
                ->each(function ($g) {
                    $email = trim((string) $g->email) ?: null;

                    $newId = DB::table('users')->insertGetId([
                        'first_name' => $g->first_name,
                        'last_name' => $g->last_name,
                        'email' => $email,
                        'phone' => $g->phone,
                        'password' => null,
                        'email_verified_at' => null,
                        'current_league_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('rounds')->where('golfer_id', $g->id)
                        ->update(['user_id' => $newId]);

                    foreach (DB::table('golfer_league')->where('golfer_id', $g->id)->get() as $p) {
                        DB::table('league_user')->insert([
                            'user_id' => $newId,
                            'league_id' => $p->league_id,
                            'role' => 'player',
                            'handicap' => $p->handicap,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Forward-only: golfer identity is discarded and cannot be rebuilt.
    }
};
