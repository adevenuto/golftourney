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
            'club_name' => 'Pine Valley Country Club',
            'course_name' => 'Pine Valley Golf Club',
            'layout_data' => [
                'hole_count' => 18,
                'teeboxes' => [[
                    'name' => 'Blue',
                    'slope' => 130,
                    'courseRating' => 72.1,
                    'holes' => $this->holesWithPar(array_fill(0, 18, 4)),
                ]],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('courses.search', ['q' => 'Pine']))
            ->assertOk()
            ->assertJsonPath('courses.0.club', 'Pine Valley Country Club')
            ->assertJsonPath('courses.0.name', 'Pine Valley Golf Club')
            ->assertJsonPath('courses.0.holes', 18)
            ->assertJsonPath('courses.0.teeboxes.0.name', 'Blue')
            ->assertJsonPath('courses.0.teeboxes.0.slope', 130)
            // 72.1 sits near par 72, not 144 — left unchanged.
            ->assertJsonPath('courses.0.teeboxes.0.rating', 72.1);
    }

    public function test_course_search_matches_club_name_not_course_name(): void
    {
        Course::factory()->create([
            'club_name' => 'Augusta National',
            'course_name' => 'Magnolia Championship Layout',
        ]);

        $user = User::factory()->create();

        // A term in the club name matches…
        $this->actingAs($user)
            ->getJson(route('courses.search', ['q' => 'Augusta']))
            ->assertOk()
            ->assertJsonPath('courses.0.club', 'Augusta National')
            ->assertJsonPath('courses.0.name', 'Magnolia Championship Layout');

        // …but a term that appears only in the course name does not.
        $this->actingAs($user)
            ->getJson(route('courses.search', ['q' => 'Magnolia']))
            ->assertOk()
            ->assertExactJson(['courses' => []]);
    }

    public function test_course_search_halves_doubled_nine_hole_ratings(): void
    {
        // Robert A. Black: a 9-hole course whose rating (63) was doubled by the
        // source API. Par sums to 33, so 63 is closer to 2*par (66) than par —
        // it must come back halved to the true 31.5.
        Course::factory()->create([
            'club_name' => 'Robert A. Black',
            'course_name' => 'Robert A. Black',
            'layout_data' => [
                'hole_count' => 9,
                'teeboxes' => [[
                    'name' => 'Blue',
                    'slope' => 104,
                    'courseRating' => 63,
                    'holes' => $this->holesWithPar([5, 3, 4, 3, 4, 4, 3, 3, 4]),
                ]],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('courses.search', ['q' => 'Robert']))
            ->assertOk()
            ->assertJsonPath('courses.0.holes', 9)
            ->assertJsonPath('courses.0.teeboxes.0.rating', 31.5)
            ->assertJsonPath('courses.0.teeboxes.0.slope', 104);
    }

    public function test_course_search_keeps_correct_nine_hole_ratings(): void
    {
        // An already-correct 9-hole rating (33.6 vs par 35) must NOT be halved —
        // the par anchor guards against double-halving.
        Course::factory()->create([
            'club_name' => 'Little Miami Golf Center',
            'course_name' => 'Little Miami Golf Center',
            'layout_data' => [
                'hole_count' => 9,
                'teeboxes' => [[
                    'name' => 'White',
                    'slope' => 110,
                    'courseRating' => 33.6,
                    'holes' => $this->holesWithPar([4, 4, 3, 4, 4, 4, 3, 5, 4]),
                ]],
            ],
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('courses.search', ['q' => 'Little Miami']))
            ->assertOk()
            ->assertJsonPath('courses.0.teeboxes.0.rating', 33.6);
    }

    /**
     * Build a layout_data "holes" map from a list of pars (stored as strings,
     * matching the source API shape).
     *
     * @param  array<int, int>  $pars
     * @return array<string, array{par: string}>
     */
    private function holesWithPar(array $pars): array
    {
        $holes = [];
        foreach ($pars as $i => $par) {
            $holes['hole-'.($i + 1)] = ['par' => (string) $par];
        }

        return $holes;
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

    public function test_an_admin_can_rename_their_league(): void
    {
        $league = League::factory()->create(['name' => 'Old Name']);
        $admin = $this->adminOf($league);

        $this->actingAs($admin)
            ->patch(route('leagues.update', $league), ['name' => 'New Name'])
            ->assertRedirect();

        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'name' => 'New Name']);
    }

    public function test_a_non_admin_cannot_rename_a_league(): void
    {
        $league = League::factory()->create(['name' => 'Untouched']);
        $player = $this->playerOf($league);

        $this->actingAs($player)
            ->patch(route('leagues.update', $league), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->assertDatabaseHas('leagues', ['id' => $league->id, 'name' => 'Untouched']);
    }

    public function test_renaming_a_league_requires_a_name(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->patchJson(route('leagues.update', $league), ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_an_admin_can_delete_a_league_and_cascade_its_rounds_and_golfers(): void
    {
        $league = League::factory()->create();
        $admin = $this->adminOf($league);

        $orphan = $this->golferIn($league);
        $this->roundFor($orphan, $league);
        $this->roundFor($orphan, $league);

        // A golfer also on another league must survive (just detached).
        $other = League::factory()->create();
        $shared = $this->golferIn($league);
        $shared->leagues()->attach($other->id, ['role' => 'player', 'handicap' => 0]);

        $this->actingAs($admin)
            ->delete(route('leagues.destroy', $league))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
        $this->assertDatabaseMissing('rounds', ['league_id' => $league->id]);
        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id]);

        // Orphan golfer gone; shared golfer kept (still in the other league).
        $this->assertDatabaseMissing('users', ['id' => $orphan->id]);
        $this->assertDatabaseHas('users', ['id' => $shared->id]);
        $this->assertDatabaseHas('league_user', ['user_id' => $shared->id, 'league_id' => $other->id]);

        // The admin no longer points at the deleted league.
        $this->assertNull($admin->fresh()->current_league_id);
    }

    public function test_a_non_admin_cannot_delete_a_league(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);

        $this->actingAs($player)
            ->delete(route('leagues.destroy', $league))
            ->assertForbidden();

        $this->assertDatabaseHas('leagues', ['id' => $league->id]);
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

    public function test_entering_a_league_switches_and_redirects_to_the_roster(): void
    {
        $a = League::factory()->create();
        $b = League::factory()->create();
        $user = User::factory()->create(['current_league_id' => $a->id]);
        $a->members()->attach($user->id, ['role' => 'admin']);
        $b->members()->attach($user->id, ['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('leagues.switch', $b), ['enter' => true])
            ->assertRedirect(route('golfers.index'));

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

    public function test_dashboard_exposes_the_leagues_course_club_and_name(): void
    {
        $course = Course::factory()->create([
            'club_name' => 'Augusta National',
            'course_name' => 'Magnolia Course',
        ]);
        $league = League::factory()->create(['course_id' => $course->id]);

        $this->actingAs($this->adminOf($league))
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('leagues.0.club_name', 'Augusta National')
                ->where('leagues.0.course_name', 'Magnolia Course')
            );
    }
}
