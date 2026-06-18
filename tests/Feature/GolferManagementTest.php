<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GolferManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_create_a_golfer_with_name_normalized(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/create/golfer', [
                'first_name' => 'John',
                'last_name' => 'Milne',
                'email' => 'john@example.com',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('golfers', [
            'first_name' => 'john',
            'last_name' => 'milne',
            'email' => 'john@example.com',
        ]);
    }

    public function test_creating_a_golfer_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/create/golfer', ['email' => 'x@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_admin_can_update_a_golfer(): void
    {
        $golfer = Golfer::factory()->create();

        $this->actingAs($this->admin())
            ->postJson("/golfers/{$golfer->id}/edit", [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'handicap' => 12.5,
                'email' => 'jane@example.com',
            ])
            ->assertOk();

        $this->assertDatabaseHas('golfers', [
            'id' => $golfer->id,
            'first_name' => 'jane',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_deleting_a_golfer_cascades_their_rounds(): void
    {
        $golfer = Golfer::factory()->create();
        Round::factory()->count(3)->for($golfer)->create();

        $this->actingAs($this->admin())
            ->deleteJson("/golfers/{$golfer->id}")
            ->assertOk();

        $this->assertDatabaseMissing('golfers', ['id' => $golfer->id]);
        $this->assertDatabaseMissing('rounds', ['golfer_id' => $golfer->id]);
    }

    public function test_index_returns_golfers_with_round_counts_ordered(): void
    {
        $busy = Golfer::factory()->create();
        Round::factory()->count(3)->for($busy)->create();
        $quiet = Golfer::factory()->create();
        Round::factory()->count(1)->for($quiet)->create();

        $response = $this->actingAs($this->admin())
            ->getJson('/golfers-list')
            ->assertOk()
            ->assertJsonStructure(['golfers' => [['id', 'first_name', 'number_of_rounds']]]);

        $golfers = $response->json('golfers');
        $this->assertSame($busy->id, $golfers[0]['id']);
        $this->assertSame(3, $golfers[0]['number_of_rounds']);
    }
}
