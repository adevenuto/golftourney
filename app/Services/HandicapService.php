<?php

namespace App\Services;

use App\Models\League;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Calculates a user's handicap within a league.
 *
 * Rule: average the score differentials of the best `counting_rounds` scoring
 * rounds out of the user's most recent `recent_rounds` rounds *in that
 * league*, using the league's course rating/slope. The handicap is stored on
 * the league_user pivot (a user can have a different handicap per league).
 */
class HandicapService
{
    /** USGA standard slope rating (universal). */
    public const STANDARD_SLOPE = 113;

    /**
     * The user's best counting rounds in the league.
     *
     * @return Collection<int, Round>
     */
    public function countingRounds(User $user, League $league): Collection
    {
        return $user->rounds()
            ->where('league_id', $league->id)
            ->whereNotNull('score')
            ->orderByDesc('created_at')
            ->limit($league->recent_rounds)
            ->get()
            ->sortBy('score')
            ->take($league->counting_rounds)
            ->values();
    }

    /**
     * The score differential for one round score, using the league's course.
     */
    public function scoreDifferential(int $score, League $league): float
    {
        return ($score - (float) $league->course_rating) * self::STANDARD_SLOPE / $league->slope_rating;
    }

    /**
     * Average the differentials of the given rounds into a handicap.
     *
     * @param  Collection<int, Round>  $rounds
     */
    public function calculate(Collection $rounds, League $league): float
    {
        if ($rounds->isEmpty()) {
            return 0.00;
        }

        $sum = $rounds->sum(fn ($round) => $this->scoreDifferential((int) $round->score, $league));

        return round($sum / $rounds->count(), 2);
    }

    /**
     * Recalculate and persist a user's handicap in a league (on the pivot).
     * Returns the new value.
     */
    public function recalculateFor(User $user, League $league): float
    {
        $handicap = $this->calculate($this->countingRounds($user, $league), $league);

        $user->leagues()->updateExistingPivot($league->id, ['handicap' => $handicap]);

        // The roster shows this handicap and the round count — both just changed.
        $league->forgetRosterCache();

        return $handicap;
    }
}
