<?php

namespace Tests\Feature;

use App\Models\Golfer;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
            ->post(route('golfers.store'), [
                'first_name' => 'John',
                'last_name' => 'Milne',
                'email' => 'john@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('golfers', [
            'first_name' => 'john',
            'last_name' => 'milne',
            'email' => 'john@example.com',
        ]);
    }

    public function test_creating_a_golfer_requires_a_name(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('golfers.store'), ['email' => 'x@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_admin_can_update_a_golfer(): void
    {
        $golfer = Golfer::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('golfers.update', $golfer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ])
            ->assertRedirect();

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
            ->delete(route('golfers.destroy', $golfer))
            ->assertRedirect();

        $this->assertDatabaseMissing('golfers', ['id' => $golfer->id]);
        $this->assertDatabaseMissing('rounds', ['golfer_id' => $golfer->id]);
    }

    public function test_any_authenticated_user_can_export_handicaps_pdf(): void
    {
        Golfer::factory()->count(2)->create();

        $this->actingAs(User::factory()->create()) // a player, not an admin
            ->get(route('golfers.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_index_renders_inertia_with_round_counts(): void
    {
        $busy = Golfer::factory()->create(['last_name' => 'aaa']);
        Round::factory()->count(3)->for($busy)->create();
        $quiet = Golfer::factory()->create(['last_name' => 'zzz']);
        Round::factory()->count(1)->for($quiet)->create();

        $this->actingAs($this->admin())
            ->get(route('golfers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Golfers/Index')
                ->has('golfers', 2)
                ->where('golfers.0.last_name', 'aaa')
                ->where('golfers.0.number_of_rounds', 3)
            );
    }
}
