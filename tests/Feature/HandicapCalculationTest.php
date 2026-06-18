<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Services\HandicapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandicapCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_counting_rounds_takes_the_best_eight_of_the_last_twenty(): void
    {
        $golfer = Golfer::factory()->create();

        // 20 recent rounds: eight good (40), twelve poor (60).
        foreach (range(0, 19) as $i) {
            Round::factory()->for($golfer)->create([
                'score' => $i < 8 ? 40 : 60,
                'created_at' => now()->subDays($i),
            ]);
        }

        // 5 older rounds with great scores that must be excluded by the 20-round window.
        foreach (range(0, 4) as $i) {
            Round::factory()->for($golfer)->create([
                'score' => 30,
                'created_at' => now()->subYears(5)->subDays($i),
            ]);
        }

        $counting = app(HandicapService::class)->countingRounds($golfer);

        $this->assertCount(8, $counting);
        $this->assertEqualsCanonicalizing([40, 40, 40, 40, 40, 40, 40, 40], $counting->pluck('score')->all());
    }

    public function test_recalculate_persists_the_handicap(): void
    {
        $golfer = Golfer::factory()->create(['handicap' => 0]);
        Round::factory()->for($golfer)->create(['score' => 40, 'created_at' => now()]);

        $handicap = app(HandicapService::class)->recalculateFor($golfer);

        // (40 - 31.5) * 113 / 104 = 9.24
        $this->assertSame(9.24, $handicap);
        $this->assertEquals(9.24, $golfer->fresh()->handicap);
    }
}
