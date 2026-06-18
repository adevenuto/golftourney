<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoundManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_create_a_round_and_handicap_is_recalculated(): void
    {
        $golfer = Golfer::factory()->create(['handicap' => 0]);

        $this->actingAs($this->admin())
            ->post(route('rounds.store', $golfer), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rounds', ['golfer_id' => $golfer->id, 'score' => 40]);
        // (40 - 31.5) * 113 / 104 = 9.24
        $this->assertEquals(9.24, $golfer->fresh()->handicap);
    }

    public function test_storing_a_round_for_a_missing_golfer_404s(): void
    {
        $this->actingAs($this->admin())
            ->post(route('rounds.store', 99999), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertNotFound();
    }

    public function test_score_is_bounded(): void
    {
        $golfer = Golfer::factory()->create();

        $this->actingAs($this->admin())
            ->postJson(route('rounds.store', $golfer), [
                'score' => 999,
                'created_at' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_admin_can_edit_a_round_and_handicap_is_recalculated(): void
    {
        $golfer = Golfer::factory()->create();
        $round = Round::factory()->for($golfer)->create(['score' => 60, 'created_at' => now()]);

        $this->actingAs($this->admin())
            ->put(route('rounds.update', $round), [
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rounds', ['id' => $round->id, 'score' => 40]);
        $this->assertEquals(9.24, $golfer->fresh()->handicap);
    }

    public function test_admin_can_delete_a_round_and_handicap_is_recalculated(): void
    {
        $golfer = Golfer::factory()->create();
        $round = Round::factory()->for($golfer)->create(['score' => 40]);

        $this->actingAs($this->admin())
            ->delete(route('rounds.destroy', $round))
            ->assertRedirect();

        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
        // No rounds left -> handicap resets to 0.
        $this->assertEquals(0, $golfer->fresh()->handicap);
    }

    public function test_index_renders_inertia_with_rounds_and_counting_ids(): void
    {
        $golfer = Golfer::factory()->create();
        Round::factory()->count(5)->for($golfer)->create();

        $this->actingAs($this->admin())
            ->get(route('golfers.rounds', $golfer))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Rounds/Index')
                ->where('golfer.id', $golfer->id)
                ->has('rounds', 5)
                ->has('countingRoundIds')
            );
    }
}
