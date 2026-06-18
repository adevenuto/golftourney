<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class LeagueManagementTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_course_search_requires_at_least_three_characters(): void
    {
        Course::factory()->create(['course_name' => 'Pine Valley']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('courses.search', ['q' => 'Pi']))
            ->assertOk()
            ->assertExactJson(['courses' => []]);
    }

    public function test_course_search_returns_matches_with_teeboxes(): void
    {
        Course::factory()->create([
            'course_name' => 'Pine Valley Golf Club',
            'layout_data' => [
                'hole_count' => 18,
                'teeboxes' => [['name' => 'Blue', 'slope' => 130, 'courseRating' => 72.1]],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('courses.search', ['q' => 'Pine']))
            ->assertOk()
            ->assertJsonPath('courses.0.name', 'Pine Valley Golf Club')
            ->assertJsonPath('courses.0.holes', 18)
            ->assertJsonPath('courses.0.teeboxes.0.name', 'Blue')
            ->assertJsonPath('courses.0.teeboxes.0.slope', 130);
    }

    public function test_creating_a_league_makes_the_creator_admin_and_current(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('leagues.store'), [
                'name' => 'My League',
                'course_rating' => 31.5,
                'slope_rating' => 104,
                'recent_rounds' => 20,
                'counting_rounds' => 8,
            ])
            ->assertRedirect(route('golfers.index'));

        $league = League::where('owner_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('league_user', [
            'user_id' => $user->id,
            'league_id' => $league->id,
            'role' => 'admin',
        ]);
        $this->assertSame($league->id, $user->fresh()->current_league_id);
    }

    public function test_creating_a_league_validates(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('leagues.store'), ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'course_rating', 'slope_rating']);
    }

    public function test_a_member_can_switch_their_active_league(): void
    {
        $a = League::factory()->create();
        $b = League::factory()->create();
        $user = User::factory()->create(['current_league_id' => $a->id]);
        $a->members()->attach($user->id, ['role' => 'player']);
        $b->members()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('leagues.switch', $b))
            ->assertRedirect();

        $this->assertSame($b->id, $user->fresh()->current_league_id);
    }

    public function test_cannot_switch_to_a_league_you_are_not_in(): void
    {
        $user = User::factory()->create();
        $other = League::factory()->create();

        $this->actingAs($user)
            ->post(route('leagues.switch', $other))
            ->assertForbidden();
    }

    public function test_dashboard_lists_the_users_leagues(): void
    {
        $league = League::factory()->create();
        $user = $this->adminOf($league);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('leagues', 1)
                ->where('leagues.0.name', $league->name)
                ->where('leagues.0.role', 'admin')
                ->where('leagues.0.is_current', true)
            );
    }
}
