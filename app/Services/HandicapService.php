<?php

namespace App\Services;

use App\Models\League;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * WHS-style handicap calculation (see HANDICAP_RULES.md).
 *
 * A player has one portable Handicap Index computed from the lowest Score
 * Differentials of their most recent 20 eligible rounds across ALL leagues.
 * 9-hole rounds are normalized to 18-hole-equivalent differentials (doubled) so
 * everything pools onto one 18-hole scale. A Course Handicap is then derived per
 * league from that index, the course rating/slope, and par. Nothing is stored
 * per-league — the index lives on the user; course handicaps are derived.
 *
 * AGS = raw gross score: we only store round totals, so the WHS net-double-bogey
 * cap is skipped (its documented fallback). PCC, caps, and ESR are out of scope.
 */
class HandicapService
{
    /** USGA standard slope rating (universal). */
    public const STANDARD_SLOPE = 113;

    /** Only the most recent N eligible rounds feed the index. */
    public const RECENT_WINDOW = 20;

    /** Minimum eligible rounds before an index can be produced. */
    public const MINIMUM_ROUNDS = 3;

    /**
     * The 18-hole-equivalent Score Differential for one round, or null if the
     * round lacks the course data needed to score it. Each round carries its own
     * snapshotted course context (league rounds copy their league at creation;
     * casual, league-less rounds copy the chosen course + teebox), so rounds are
     * self-contained.
     */
    public function differentialFor(Round $round): ?float
    {
        $rating = (float) ($round->course_rating ?? 0);
        $slope = (int) ($round->slope_rating ?? 0);
        $par = (int) ($round->par ?? 0);
        $holes = (int) ($round->holes ?? 0);

        if ($rating <= 0 || $slope <= 0 || $par <= 0) {
            return null;
        }

        // Differential = (AGS − CourseRating) × 113 / Slope. AGS = gross score.
        $base = ((int) $round->score - $rating) * self::STANDARD_SLOPE / $slope;

        // A 9-hole round is half a round; double it onto the 18-hole scale.
        return $holes === 9 ? $base * 2 : $base;
    }

    /**
     * The player's WHS Handicap Index, or null if they have too few eligible
     * rounds. Computed from the lowest differentials of the most recent 20,
     * using the short-record table (see HANDICAP_RULES.md).
     */
    public function indexFor(User $user): ?float
    {
        $differentials = $this->recentEligible($user)->pluck('diff');

        $table = $this->shortRecord($differentials->count());

        if (! $table) {
            return null;
        }

        [$lowestUsed, $adjustment] = $table;

        $average = $differentials->sort()->take($lowestUsed)->avg();

        return round($average + $adjustment, 1);
    }

    /**
     * The ids of the rounds whose differentials are selected for the index
     * (the lowest N of the recent 20) — used to flag "counts" on the UI.
     *
     * @return array<int, int>
     */
    public function usedRoundIds(User $user): array
    {
        $eligible = $this->recentEligible($user);

        $table = $this->shortRecord($eligible->count());

        if (! $table) {
            return [];
        }

        return $eligible->sortBy('diff')->take($table[0])->pluck('id')->all();
    }

    /**
     * How many rounds actually feed the index right now: the count of eligible
     * rounds in the recent window, capped at 20. Used to phrase the "most recent
     * N rounds" copy so it reflects players with a short record.
     */
    public function recentWindowSize(User $user): int
    {
        return $this->recentEligible($user)->count();
    }

    /**
     * A player's Course Handicap for a league, derived from their effective
     * index + the league's slope/rating/par. Null if no index or no par.
     * 9-hole courses use half the (18-hole) index.
     */
    public function courseHandicap(User $user, League $league): ?int
    {
        return $this->courseHandicapForIndex($user->effectiveHandicapIndex(), $league);
    }

    /**
     * Course Handicap for a raw index value (used for roster rows that aren't
     * hydrated User models). Null if the index or par is missing.
     */
    public function courseHandicapForIndex(?float $index, League $league): ?int
    {
        if (is_null($index) || $league->course_rating <= 0 || $league->slope_rating <= 0 || ($league->par ?? 0) <= 0) {
            return null;
        }

        $hi = $league->holes === 9 ? $index / 2 : $index;

        return (int) round($hi * $league->slope_rating / self::STANDARD_SLOPE + ((float) $league->course_rating - $league->par));
    }

    /**
     * Recompute and persist the user's global index, then bust the roster cache
     * for every league they're in (each league's course handicaps derive from
     * the index, so they all shift when it changes).
     */
    public function recalculateFor(User $user): void
    {
        $user->update(['handicap_index' => $this->indexFor($user)]);

        foreach ($user->leagues as $league) {
            $league->forgetRosterCache();
        }
    }

    /**
     * Format an index for display: null → "N/A", scratch-or-better → "+2.1".
     */
    public function formatIndex(?float $index): string
    {
        if (is_null($index)) {
            return 'N/A';
        }

        return $index < 0
            ? '+'.number_format(abs($index), 1)
            : number_format($index, 1);
    }

    /**
     * The most recent eligible rounds (max 20), newest first, each as
     * ['id' => int, 'diff' => float].
     *
     * @return Collection<int, array{id: int, diff: float}>
     */
    private function recentEligible(User $user): Collection
    {
        return $user->rounds()
            ->with('league')
            ->whereNotNull('score')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Round $round): array => ['id' => $round->id, 'diff' => $this->differentialFor($round)])
            ->filter(fn (array $row): bool => ! is_null($row['diff']))
            ->take(self::RECENT_WINDOW)
            ->values();
    }

    /**
     * WHS short-record table: given the count of eligible rounds, how many of
     * the lowest differentials to average and what adjustment to apply. Null
     * below the 3-round minimum.
     *
     * @return array{0: int, 1: float}|null [lowestUsed, adjustment]
     */
    private function shortRecord(int $count): ?array
    {
        return match (true) {
            $count < self::MINIMUM_ROUNDS => null,
            $count === 3 => [1, -2.0],
            $count === 4 => [1, -1.0],
            $count === 5 => [1, 0.0],
            $count === 6 => [2, -1.0],
            $count <= 8 => [2, 0.0],
            $count <= 11 => [3, 0.0],
            $count <= 14 => [4, 0.0],
            $count <= 16 => [5, 0.0],
            $count <= 18 => [6, 0.0],
            $count === 19 => [7, 0.0],
            default => [8, 0.0],
        };
    }
}
