<?php

namespace Tests\Feature;

use App\Models\League;
use App\Services\HandicapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class HandicapCalculationTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_counting_rounds_takes_the_best_eight_of_the_last_twenty(): void
    {
        $league = League::factory()->create(); // recent 20, counting 8
        $golfer = $this->golferIn($league);

        // 20 recent rounds: eight good (40), twelve poor (60).
        foreach (range(0, 19) as $i) {
            $this->roundFor($golfer, $league, [
                'score' => $i < 8 ? 40 : 60,
                'created_at' => now()->subDays($i),
            ]);
        }

        // 5 older rounds with great scores, excluded by the 20-round window.
        foreach (range(0, 4) as $i) {
            $this->roundFor($golfer, $league, [
                'score' => 30,
                'created_at' => now()->subYears(5)->subDays($i),
            ]);
        }

        $counting = app(HandicapService::class)->countingRounds($golfer, $league);

        $this->assertCount(8, $counting);
        $this->assertEqualsCanonicalizing(
            [40, 40, 40, 40, 40, 40, 40, 40],
            $counting->pluck('score')->all()
        );
    }

    public function test_recalculate_persists_the_handicap_on_the_pivot(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $this->roundFor($golfer, $league, ['score' => 40, 'created_at' => now()]);

        $handicap = app(HandicapService::class)->recalculateFor($golfer, $league);

        // (40 - 31.5) * 113 / 104 = 9.24
        $this->assertSame(9.24, $handicap);
        $this->assertDatabaseHas('golfer_league', [
            'golfer_id' => $golfer->id,
            'league_id' => $league->id,
            'handicap' => 9.24,
        ]);
    }
}
