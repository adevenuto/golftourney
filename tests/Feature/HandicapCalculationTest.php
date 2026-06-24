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

    public function test_index_reflects_the_best_eight_of_the_last_twenty(): void
    {
        $league = League::factory()->create(); // 9-hole, CR 31.5 / slope 104 / par 33
        $golfer = $this->golferIn($league);

        // 20 recent rounds: eight good (40), twelve poor (60).
        foreach (range(0, 19) as $i) {
            $this->roundFor($golfer, $league, [
                'score' => $i < 8 ? 40 : 60,
                'created_at' => now()->subDays($i),
            ]);
        }

        // 5 older great rounds, excluded by the 20-round window.
        foreach (range(0, 4) as $i) {
            $this->roundFor($golfer, $league, [
                'score' => 30,
                'created_at' => now()->subYears(5)->subDays($i),
            ]);
        }

        app(HandicapService::class)->recalculateFor($golfer);

        // Lowest 8 of the recent 20 are the eight 40s: D = 2*(40-31.5)*113/104 = 18.47 -> 18.5.
        $this->assertEquals(18.5, $golfer->fresh()->handicap_index);
    }

    public function test_recalculate_persists_the_index_on_the_user(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        foreach ([40, 60, 60] as $score) {
            $this->roundFor($golfer, $league, ['score' => $score]);
        }

        app(HandicapService::class)->recalculateFor($golfer);

        // 3 rounds -> lowest 1 (the 40) with -2.0 -> 16.5.
        $this->assertEquals(16.5, $golfer->fresh()->handicap_index);
    }
}
