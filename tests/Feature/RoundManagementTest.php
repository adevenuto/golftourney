<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->postJson('/rounds/store', [
                'golfer_id' => $golfer->id,
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('rounds', ['golfer_id' => $golfer->id, 'score' => 40]);
        // (40 - 31.5) * 113 / 104 = 9.24
        $this->assertEquals(9.24, $golfer->fresh()->handicap);
    }

    public function test_creating_a_round_requires_a_valid_golfer(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/rounds/store', [
                'golfer_id' => 99999,
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['golfer_id']);
    }

    public function test_score_is_bounded(): void
    {
        $golfer = Golfer::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/rounds/store', [
                'golfer_id' => $golfer->id,
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
            ->postJson('/rounds/edit', [
                'id' => $round->id,
                'score' => 40,
                'created_at' => now()->toDateString(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('rounds', ['id' => $round->id, 'score' => 40]);
        $this->assertEquals(9.24, $golfer->fresh()->handicap);
    }

    public function test_admin_can_delete_a_round_and_handicap_is_recalculated(): void
    {
        $golfer = Golfer::factory()->create();
        $round = Round::factory()->for($golfer)->create(['score' => 40]);

        $this->actingAs($this->admin())
            ->deleteJson("/rounds/{$round->id}")
            ->assertOk();

        $this->assertDatabaseMissing('rounds', ['id' => $round->id]);
        // No rounds left -> handicap resets to 0.
        $this->assertEquals(0, $golfer->fresh()->handicap);
    }

    public function test_index_returns_counting_rounds_and_total(): void
    {
        $golfer = Golfer::factory()->create();
        Round::factory()->count(5)->for($golfer)->create();

        $this->actingAs($this->admin())
            ->getJson("/golfers/{$golfer->id}/rounds")
            ->assertOk()
            ->assertJsonPath('rounds.total', 5)
            ->assertJsonStructure(['rounds' => ['latest', 'total']]);
    }
}
