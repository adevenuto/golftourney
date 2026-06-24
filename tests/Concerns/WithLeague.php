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
     * A login-less roster user on the given league, optionally seeded with a
     * known Handicap Index (set directly on the user).
     */
    protected function golferIn(League $league, array $attributes = [], ?float $index = null): User
    {
        $user = User::factory()->roster()->create($attributes);

        if (! is_null($index)) {
            $user->update(['handicap_index' => $index]);
        }

        $user->leagues()->attach($league->id, ['role' => 'player']);

        return $user;
    }

    /**
     * A round for a roster user in a league (snapshotting the league's context).
     */
    protected function roundFor(User $user, League $league, array $attributes = []): Round
    {
        return Round::factory()
            ->for($user)
            ->create(array_merge([
                'league_id' => $league->id,
                'course_rating' => $league->course_rating,
                'slope_rating' => $league->slope_rating,
                'par' => $league->par,
                'holes' => $league->holes,
            ], $attributes));
    }
}
