<?php

namespace Tests\Concerns;

use App\Models\Golfer;
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
     * A golfer on the given league's roster (with an optional pivot handicap).
     */
    protected function golferIn(League $league, array $attributes = [], float $handicap = 0): Golfer
    {
        $golfer = Golfer::factory()->create($attributes);
        $golfer->leagues()->attach($league->id, ['handicap' => $handicap]);

        return $golfer;
    }

    /**
     * A round for a golfer in a league.
     */
    protected function roundFor(Golfer $golfer, League $league, array $attributes = []): Round
    {
        return Round::factory()
            ->for($golfer)
            ->create(array_merge(['league_id' => $league->id], $attributes));
    }
}
