<?php

namespace Tests\Feature;

use App\Models\League;
use App\Services\HandicapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class HandicapsPageTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_guests_cannot_view_the_handicaps_page(): void
    {
        $this->get(route('handicaps'))->assertRedirect(route('login'));
    }

    public function test_authed_user_sees_the_explainer_with_constants(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->get(route('handicaps'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Handicaps')
                ->where('constants.recentWindow', 20)
                ->where('constants.minimumRounds', 3)
                ->where('constants.standardSlope', 113)
                ->has('you')
            );
    }

    public function test_it_shows_the_viewers_own_course_handicap(): void
    {
        $league = League::factory()->create(); // 9-hole, CR 31.5 / slope 104 / par 33
        $user = $this->adminOf($league);
        foreach ([40, 40, 40] as $score) {
            $this->roundFor($user, $league, ['score' => $score]);
        }
        app(HandicapService::class)->recalculateFor($user);

        $this->actingAs($user)
            ->get(route('handicaps'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('you.league', $league->name)
                ->where('you.index', '16.5')
                // round((16.5/2)*104/113 + (31.5-33)) = round(6.09) = 6
                ->where('you.course_handicap', 6)
            );
    }
}
