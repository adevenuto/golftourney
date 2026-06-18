<?php

namespace Tests\Feature;

use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class RoundManagementTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_admin_can_create_a_round_and_handicap_is_recalculated(): void
    {
        $league = League::factory()->create(); // Black League numbers: 31.5 / 104
        $golfer = $this->golferIn($league);

        $this->actingAs($this->adminOf($league))
            ->post(route('rounds.store', $golfer), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rounds', [
            'golfer_id' => $golfer->id,
            'league_id' => $league->id,
            'score' => 40,
        ]);
        // (40 - 31.5) * 113 / 104 = 9.24 — stored on the pivot.
        $this->assertDatabaseHas('golfer_league', [
            'golfer_id' => $golfer->id,
            'league_id' => $league->id,
            'handicap' => 9.24,
        ]);
    }

    public function test_storing_a_round_for_a_golfer_outside_the_league_404s(): void
    {
        $league = League::factory()->create();
        $outsider = $this->golferIn(League::factory()->create());

        $this->actingAs($this->adminOf($league))
            ->post(route('rounds.store', $outsider), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertNotFound();
    }

    public function test_score_is_bounded(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);

        $this->actingAs($this->adminOf($league))
            ->postJson(route('rounds.store', $golfer), [
                'score' => 999,
                'created_at' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_admin_can_edit_a_round_and_handicap_is_recalculated(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $round = $this->roundFor($golfer, $league, ['score' => 60, 'created_at' => now()]);

        $this->actingAs($this->adminOf($league))
            ->put(route('rounds.update', $round), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rounds', ['id' => $round->id, 'score' => 40]);
        $this->assertDatabaseHas('golfer_league', [
            'golfer_id' => $golfer->id,
            'league_id' => $league->id,
            'handicap' => 9.24,
        ]);
    }

    public function test_admin_can_delete_a_round_and_handicap_is_recalculated(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league, [], 9.24);
        $round = $this->roundFor($golfer, $league, ['score' => 40]);

        $this->actingAs($this->adminOf($league))
            ->delete(route('rounds.destroy', $round))
            ->assertRedirect();

        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
        // No rounds left -> handicap resets to 0.
        $this->assertDatabaseHas('golfer_league', [
            'golfer_id' => $golfer->id,
            'league_id' => $league->id,
            'handicap' => 0,
        ]);
    }

    public function test_index_renders_inertia_with_rounds_and_counting_ids(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        for ($i = 0; $i < 5; $i++) {
            $this->roundFor($golfer, $league);
        }

        $this->actingAs($this->adminOf($league))
            ->get(route('golfers.rounds', $golfer))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rounds/Index')
                ->where('golfer.id', $golfer->id)
                ->has('rounds', 5)
                ->has('countingRoundIds')
            );
    }
}
