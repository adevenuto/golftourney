<?php

namespace Tests\Concerns;

use App\Models\League;
use App\Models\Round;
use App\Models\User;

trait WithLeague
{
    /**
     * A user who is an admin of the given league (and has it as current).
     */
    protected function adminOf(League $league): User
    {
        $user = User::factory()->create(['current_league_id' => $league->id]);
        $league->members()->attach($user->id, ['role' => 'admin']);

        return $user;
    }

    /**
     * A user who is a player in the given league (and has it as current).
     */
    protected function playerOf(League $league): User
    {
        $user = User::factory()->create(['current_league_id' => $league->id]);
        $league->members()->attach($user->id, ['role' => 'player']);

        return $user;
    }

    /**
     * A login-less roster user on the given league (with an optional handicap).
     */
    protected function golferIn(League $league, array $attributes = [], float $handicap = 0): User
    {
        $user = User::factory()->roster()->create($attributes);
        $user->leagues()->attach($league->id, ['role' => 'player', 'handicap' => $handicap]);

        return $user;
    }

    /**
     * A round for a roster user in a league.
     */
    protected function roundFor(User $user, League $league, array $attributes = []): Round
    {
        return Round::factory()
            ->for($user)
            ->create(array_merge(['league_id' => $league->id], $attributes));
    }
}
