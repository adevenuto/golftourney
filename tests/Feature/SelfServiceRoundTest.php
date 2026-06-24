<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class SelfServiceRoundTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    /** An 18-hole catalog course (par 72, slope 113, rating 72) with a Blue tee. */
    private function course(): Course
    {
        return Course::factory()->create([
            'layout_data' => [
                'hole_count' => 18,
                'teeboxes' => [[
                    'name' => 'Blue',
                    'slope' => 113,
                    'courseRating' => 72.0,
                    'holes' => collect(range(1, 18))->mapWithKeys(fn ($i) => ['hole-'.$i => ['par' => '4']])->all(),
                ]],
            ],
        ]);
    }

    public function test_player_can_add_their_own_casual_round(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league); // login-capable, not an admin
        $course = $this->course();

        $this->actingAs($player)
            ->post(route('rounds.store', $player), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => $course->id,
                'teebox' => 'Blue',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rounds', [
            'user_id' => $player->id,
            'league_id' => null,
            'score' => 90,
        ]);
    }

    public function test_player_cannot_add_a_league_round(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);

        $this->actingAs($player)
            ->post(route('rounds.store', $player), [ // no course_id => league round
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_player_cannot_manage_another_players_rounds(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);
        $other = $this->golferIn($league);
        $course = $this->course();

        $this->actingAs($player)
            ->post(route('rounds.store', $other), [
                'score' => 90,
                'created_at' => now()->toDateString(),
                'course_id' => $course->id,
                'teebox' => 'Blue',
            ])
            ->assertForbidden();
    }

    public function test_player_can_edit_and_delete_their_own_casual_round(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);
        $course = $this->course();
        $this->actingAs($player)->post(route('rounds.store', $player), [
            'score' => 90, 'created_at' => now()->toDateString(), 'course_id' => $course->id, 'teebox' => 'Blue',
        ]);
        $round = $player->rounds()->whereNull('league_id')->firstOrFail();

        $this->actingAs($player)
            ->put(route('rounds.update', $round), ['score' => 85, 'created_at' => now()->toDateString()])
            ->assertRedirect();
        $this->assertDatabaseHas('rounds', ['id' => $round->id, 'score' => 85]);

        $this->actingAs($player)
            ->delete(route('rounds.destroy', $round))
            ->assertRedirect();
        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
    }

    public function test_player_cannot_edit_their_own_league_round(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);
        $round = $this->roundFor($player, $league, ['score' => 40]); // league round

        $this->actingAs($player)
            ->put(route('rounds.update', $round), ['score' => 50, 'created_at' => now()->toDateString()])
            ->assertForbidden();
    }
}
