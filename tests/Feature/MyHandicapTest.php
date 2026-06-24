<?php

namespace Tests\Feature;

use App\Models\League;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class MyHandicapTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('my-handicap'))->assertRedirect(route('login'));
    }

    public function test_a_player_sees_their_own_handicap_page(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);
        $this->roundFor($player, $league, ['score' => 90]);

        $this->actingAs($player)
            ->get(route('my-handicap'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MyHandicap')
                ->where('userId', $player->id)
                ->has('rounds', 1)
                ->has('index')
            );
    }
}
