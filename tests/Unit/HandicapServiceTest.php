<?php

namespace Tests\Unit;

use App\Models\League;
use App\Models\Round;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class HandicapServiceTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    private HandicapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HandicapService;
    }

    /** A 9-hole course with the Black League numbers (CR 31.5 / slope 104 / par 33). */
    private function nineHoleLeague(): League
    {
        return new League(['holes' => 9, 'course_rating' => 31.5, 'slope_rating' => 104, 'par' => 33]);
    }

    /** A standard 18-hole course of par 72 on the standard slope. */
    private function eighteenHoleLeague(): League
    {
        return new League(['holes' => 18, 'course_rating' => 72.0, 'slope_rating' => 113, 'par' => 72]);
    }

    private function roundWith(int $score, League $league): Round
    {
        // Rounds carry their own snapshotted course context.
        return new Round([
            'score' => $score,
            'course_rating' => $league->course_rating,
            'slope_rating' => $league->slope_rating,
            'par' => $league->par,
            'holes' => $league->holes,
        ]);
    }

    public function test_differential_for_an_18_hole_round(): void
    {
        // (90 - 72) * 113 / 113 = 18.0
        $this->assertEqualsWithDelta(18.0, $this->service->differentialFor($this->roundWith(90, $this->eighteenHoleLeague())), 0.001);
    }

    public function test_nine_hole_differential_is_doubled_to_18_hole_equivalent(): void
    {
        // 2 * (45 - 31.5) * 113 / 104 = 29.3365
        $this->assertEqualsWithDelta(29.3365, $this->service->differentialFor($this->roundWith(45, $this->nineHoleLeague())), 0.001);
    }

    public function test_differential_is_null_without_par(): void
    {
        $league = new League(['holes' => 9, 'course_rating' => 31.5, 'slope_rating' => 104, 'par' => null]);

        $this->assertNull($this->service->differentialFor($this->roundWith(45, $league)));
    }

    public function test_differential_reads_the_rounds_own_context_for_casual_rounds(): void
    {
        // An 18-hole casual round (no league) scored from its own snapshot — not doubled.
        $round = new Round(['score' => 90, 'course_rating' => 72.0, 'slope_rating' => 113, 'par' => 72, 'holes' => 18]);

        $this->assertEqualsWithDelta(18.0, $this->service->differentialFor($round), 0.001);
    }

    public function test_a_nine_hole_casual_round_is_doubled_from_its_own_context(): void
    {
        $round = new Round(['score' => 45, 'course_rating' => 31.5, 'slope_rating' => 104, 'par' => 33, 'holes' => 9]);

        $this->assertEqualsWithDelta(29.3365, $this->service->differentialFor($round), 0.001);
    }

    public function test_course_handicap_for_a_nine_hole_course_uses_half_the_index(): void
    {
        // hi = 18.5/2 = 9.25; round(9.25 * 104/113 + (31.5 - 33)) = round(7.01) = 7
        $this->assertSame(7, $this->service->courseHandicapForIndex(18.5, $this->nineHoleLeague()));
    }

    public function test_course_handicap_for_an_18_hole_course(): void
    {
        // round(10.0 * 113/113 + (72 - 72)) = 10
        $this->assertSame(10, $this->service->courseHandicapForIndex(10.0, $this->eighteenHoleLeague()));
    }

    public function test_course_handicap_is_null_without_an_index(): void
    {
        $this->assertNull($this->service->courseHandicapForIndex(null, $this->nineHoleLeague()));
    }

    public function test_format_index(): void
    {
        $this->assertSame('N/A', $this->service->formatIndex(null));
        $this->assertSame('12.3', $this->service->formatIndex(12.3));
        $this->assertSame('+2.1', $this->service->formatIndex(-2.1));
    }

    public function test_index_is_null_below_the_three_round_minimum(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $this->roundFor($golfer, $league, ['score' => 40]);
        $this->roundFor($golfer, $league, ['score' => 40]);

        $this->assertNull($this->service->indexFor($golfer));
    }

    public function test_index_uses_the_short_record_table_at_three_rounds(): void
    {
        // 3 rounds -> lowest 1, adjustment -2.0. Best is 40: D=18.4712 -> 16.5.
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        foreach ([40, 60, 60] as $score) {
            $this->roundFor($golfer, $league, ['score' => $score]);
        }

        $this->assertSame(16.5, $this->service->indexFor($golfer));
    }

    public function test_index_uses_best_eight_of_the_most_recent_twenty(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);

        // 20 recent rounds: eight 40s, twelve 60s.
        foreach (range(0, 19) as $i) {
            $this->roundFor($golfer, $league, ['score' => $i < 8 ? 40 : 60, 'created_at' => now()->subDays($i)]);
        }
        // Older great scores, outside the recent-20 window.
        foreach (range(0, 4) as $i) {
            $this->roundFor($golfer, $league, ['score' => 30, 'created_at' => now()->subYears(5)->subDays($i)]);
        }

        // avg of eight D(40)=18.4712, adjustment 0 -> 18.5
        $this->assertSame(18.5, $this->service->indexFor($golfer));
    }

    public function test_index_pools_rounds_across_leagues(): void
    {
        $a = League::factory()->create();
        $b = League::factory()->create();
        $golfer = $this->golferIn($a);
        $golfer->leagues()->attach($b->id, ['role' => 'player']);

        $this->roundFor($golfer, $a, ['score' => 40]);
        $this->roundFor($golfer, $b, ['score' => 40]);
        $this->roundFor($golfer, $b, ['score' => 40]);

        // 3 pooled rounds of 40 -> 16.5
        $this->assertSame(16.5, $this->service->indexFor($golfer));
    }

    public function test_used_round_ids_are_the_selected_lowest(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $best = $this->roundFor($golfer, $league, ['score' => 40]);
        $this->roundFor($golfer, $league, ['score' => 60]);
        $this->roundFor($golfer, $league, ['score' => 60]);

        // n=3 -> lowest 1 used: the 40.
        $this->assertSame([$best->id], $this->service->usedRoundIds($golfer));
    }

    public function test_established_index_seeds_until_a_computed_index_exists(): void
    {
        // No computed index yet (too few rounds): the established index seeds it.
        $user = User::factory()->create(['handicap_index' => null, 'manual_handicap_index' => 8.5]);
        $this->assertSame(8.5, $user->effectiveHandicapIndex());

        // Once enough rounds yield a computed index, it takes over automatically.
        $user->update(['handicap_index' => 12.0]);
        $this->assertSame(12.0, $user->fresh()->effectiveHandicapIndex());

        // Nothing computed and no seed → N/A.
        $user->update(['handicap_index' => null, 'manual_handicap_index' => null]);
        $this->assertNull($user->fresh()->effectiveHandicapIndex());
    }
}
