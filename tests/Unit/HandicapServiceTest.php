<?php

namespace Tests\Unit;

use App\Models\Round;
use App\Services\HandicapService;
use Tests\TestCase;

class HandicapServiceTest extends TestCase
{
    private HandicapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HandicapService;
    }

    public function test_score_differential_uses_course_and_slope_ratings(): void
    {
        // (45 - 31.5) * 113 / 104
        $this->assertEqualsWithDelta(14.6683, $this->service->scoreDifferential(45), 0.0001);
    }

    public function test_calculate_returns_zero_for_no_rounds(): void
    {
        $this->assertSame(0.00, $this->service->calculate(collect()));
    }

    public function test_calculate_averages_the_differentials(): void
    {
        $rounds = collect([
            new Round(['score' => 40]),
            new Round(['score' => 50]),
        ]);

        // avg of 9.2356 and 20.1010 = 14.67 (rounded to 2dp)
        $this->assertSame(14.67, $this->service->calculate($rounds));
    }
}
