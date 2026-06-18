<?php

namespace Tests\Feature;

use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class GolferManagementTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_admin_can_create_a_golfer_added_to_their_league(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
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
        $this->assertDatabaseHas('golfer_league', ['league_id' => $league->id]);
    }

    public function test_creating_a_golfer_requires_a_name(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.store'), ['email' => 'x@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_admin_can_update_a_golfer_in_their_league(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);

        $this->actingAs($this->adminOf($league))
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

    public function test_admin_cannot_touch_a_golfer_from_another_league(): void
    {
        $league = League::factory()->create();
        $otherGolfer = $this->golferIn(League::factory()->create());

        $this->actingAs($this->adminOf($league))
            ->put(route('golfers.update', $otherGolfer), [
                'first_name' => 'Nope',
                'last_name' => 'Nope',
            ])
            ->assertNotFound();
    }

    public function test_removing_a_golfer_detaches_and_deletes_their_league_rounds(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league);
        $this->roundFor($golfer, $league);
        $this->roundFor($golfer, $league);

        $this->actingAs($this->adminOf($league))
            ->delete(route('golfers.destroy', $golfer))
            ->assertRedirect();

        // Golfer had no other leagues -> fully removed, with its rounds.
        $this->assertDatabaseMissing('golfers', ['id' => $golfer->id]);
        $this->assertDatabaseMissing('rounds', ['golfer_id' => $golfer->id]);
        $this->assertDatabaseMissing('golfer_league', ['golfer_id' => $golfer->id]);
    }

    public function test_any_league_member_can_export_handicaps_pdf(): void
    {
        $league = League::factory()->create();
        $this->golferIn($league);
        $this->golferIn($league);

        $this->actingAs($this->playerOf($league)) // not an admin
            ->get(route('golfers.export', ['sort' => 'number_of_rounds', 'dir' => 'desc']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_index_renders_inertia_scoped_to_the_league(): void
    {
        $league = League::factory()->create();
        $busy = $this->golferIn($league, ['last_name' => 'aaa']);
        $this->roundFor($busy, $league);
        $this->roundFor($busy, $league);
        $this->roundFor($busy, $league);
        $quiet = $this->golferIn($league, ['last_name' => 'zzz']);
        $this->roundFor($quiet, $league);

        // A golfer in a different league must not appear.
        $this->golferIn(League::factory()->create());

        $this->actingAs($this->adminOf($league))
            ->get(route('golfers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Golfers/Index')
                ->has('golfers', 2)
                ->where('golfers.0.id', $busy->id)
                ->where('golfers.0.number_of_rounds', 3)
            );
    }
}
