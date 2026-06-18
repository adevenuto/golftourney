<?php

namespace App\Services;

use App\Models\Golfer;
use App\Models\Round;
use Illuminate\Support\Collection;

/**
 * Calculates a golfer's handicap from their recent rounds.
 *
 * Rule: average the score differentials of the best COUNTING_ROUNDS scoring
 * rounds out of their most recent RECENT_ROUNDS.
 */
class HandicapService
{
    /** Course rating for Robert A. Black (9-hole). */
    public const COURSE_RATING = 31.5;

    /** USGA standard slope rating. */
    public const STANDARD_SLOPE = 113;

    /** Course slope rating for Robert A. Black. */
    public const COURSE_SLOPE = 104;

    /** How many of the most recent rounds are considered. */
    public const RECENT_ROUNDS = 20;

    /** How many of the best recent rounds count toward the handicap. */
    public const COUNTING_ROUNDS = 8;

    /**
     * The best scoring rounds (lowest scores) from a golfer's recent rounds.
     *
     * @return Collection<int, Round>
     */
    public function countingRounds(Golfer $golfer): Collection
    {
        return $golfer->rounds()
            ->whereNotNull('score')
            ->orderByDesc('created_at')
            ->limit(self::RECENT_ROUNDS)
            ->get()
            ->sortBy('score')
            ->take(self::COUNTING_ROUNDS)
            ->values();
    }

    /**
     * The score differential for a single round score.
     */
    public function scoreDifferential(int $score): float
    {
        return ($score - self::COURSE_RATING) * self::STANDARD_SLOPE / self::COURSE_SLOPE;
    }

    /**
     * Average the differentials of the given rounds into a handicap.
     *
     * @param  Collection<int, Round>  $rounds
     */
    public function calculate(Collection $rounds): float
    {
        if ($rounds->isEmpty()) {
            return 0.00;
        }

        $sum = $rounds->sum(fn ($round) => $this->scoreDifferential((int) $round->score));

        return round($sum / $rounds->count(), 2);
    }

    /**
     * Recalculate and persist a golfer's handicap. Returns the new value.
     */
    public function recalculateFor(Golfer $golfer): float
    {
        $handicap = $this->calculate($this->countingRounds($golfer));

        $golfer->update(['handicap' => $handicap]);

        return $handicap;
    }
}
