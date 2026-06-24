<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class CasualRoundTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    /** An 18-hole catalog course (par 72, slope 113, rating 72) with a Blue tee. */
    private function eighteenHoleCourse(): Course
    {
        return Course::factory()->create([
            'club_name' => 'Pebble Test',
            'course_name' => 'Pebble Test',
            'layout_data' => [
                'hole_count' => 18,
                'teeboxes' => [[
                    'name' => 'Blue',
                    'slope' => 113,
                    'courseRating' => 72.0,
                    'holes' => collect(range(1, 18))
                        ->mapWithKeys(fn ($i) => ['hole-'.$i => ['par' => '4']])
                        ->all(),
                ]],
            ],
        ]);
    }

    public function test_admin_can_log_a_casual_round_at_another_course(): void
    {
        $league = League::factory()->create(); // 9-hole Black League numbers
        $golfer = $this->golferIn($league);
        $course = $this->eighteenHoleCourse();

        $this->actingAs($this->adminOf($league))
            ->post(route('rounds.store', $golfer), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => $course->id,
                'teebox' => 'Blue',
            ])
            ->assertRedirect();

        // Stored as a casual round (no league) with its own snapshotted context.
        $this->assertDatabaseHas('rounds', [
            'user_id' => $golfer->id,
            'league_id' => null,
            'course_id' => $course->id,
            'teebox' => 'Blue',
            'score' => 90,
            'slope_rating' => 113,
            'par' => 72,
            'holes' => 18,
        ]);
    }

    public function test_a_casual_round_feeds_the_global_index(): void
    {
        $league = League::factory()->create(); // 9-hole, par 33
        $golfer = $this->golferIn($league);
        // Two league rounds of 40 (9-hole diff doubled = 18.47 each)...
        $this->roundFor($golfer, $league, ['score' => 40]);
        $this->roundFor($golfer, $league, ['score' => 40]);
        $course = $this->eighteenHoleCourse();

        // ...plus a casual 18-hole round of 90 (diff = (90-72)*113/113 = 18.0).
        $this->actingAs($this->adminOf($league))
            ->post(route('rounds.store', $golfer), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => $course->id,
                'teebox' => 'Blue',
            ])
            ->assertRedirect();

        // 3 rounds -> lowest 1 (the 18.0 casual) with -2.0 -> 16.0.
        $this->assertEquals(16.0, $golfer->fresh()->handicap_index);
    }

    public function test_casual_round_requires_usable_tee_data(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        // A course with no per-hole par -> no derivable context.
        $course = Course::factory()->create([
            'layout_data' => ['hole_count' => 18, 'teeboxes' => [['name' => 'Blue', 'slope' => 113, 'courseRating' => 72.0]]],
        ]);

        $this->actingAs($this->adminOf($league))
            ->post(route('rounds.store', $golfer), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => $course->id,
                'teebox' => 'Blue',
            ])
            ->assertStatus(422);
    }

    public function test_store_rejects_an_unknown_course(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('rounds.store', $golfer), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => 999999,
                'teebox' => 'Blue',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_id']);
    }

    public function test_admin_can_delete_a_players_casual_round(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $course = $this->eighteenHoleCourse();

        $admin = $this->adminOf($league);
        $this->actingAs($admin)->post(route('rounds.store', $golfer), [
            'score' => 90, 'created_at' => now()->toDateString(), 'course_id' => $course->id, 'teebox' => 'Blue',
        ]);
        $round = $golfer->rounds()->whereNull('league_id')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('rounds.destroy', $round))
            ->assertRedirect();

        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
    }
}
