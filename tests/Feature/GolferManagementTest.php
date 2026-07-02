<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\WithLeague;
use Tests\TestCase;

class GolferManagementTest extends TestCase
{
    use RefreshDatabase, WithLeague;

    public function test_admin_can_batch_create_new_golfers(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->post(route('golfers.store'), [
                'golfers' => [
                    ['first_name' => 'John', 'last_name' => 'Milne', 'email' => 'john@example.com'],
                    ['first_name' => 'Jane', 'last_name' => 'Doe'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['first_name' => 'john', 'last_name' => 'milne', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['first_name' => 'jane', 'last_name' => 'doe']);
        $this->assertSame(2, $league->members()->wherePivot('role', 'player')->count());
        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'role' => 'player']);
    }

    public function test_each_batch_row_requires_a_name_or_existing_golfer(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->postJson(route('golfers.store'), ['golfers' => [['email' => 'x@example.com']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['golfers.0.first_name']);
    }

    public function test_can_reuse_an_existing_golfer_from_another_of_my_leagues(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);

        // The same user also plays in another league that has a golfer.
        $other = League::factory()->create();
        $other->members()->attach($admin->id, ['role' => 'player']);
        $reusable = $this->golferIn($other, ['first_name' => 'tiger', 'last_name' => 'woods']);

        $this->actingAs($admin)
            ->post(route('golfers.store'), ['golfers' => [['golfer_id' => $reusable->id]]])
            ->assertRedirect();

        // Attached to the current league, no duplicate roster user.
        $this->assertDatabaseHas('league_user', ['user_id' => $reusable->id, 'league_id' => $current->id]);
        $this->assertSame(1, User::whereNull('password')->count());
    }

    public function test_cannot_reuse_a_golfer_from_a_league_im_not_in(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);

        // A golfer in a league the admin has no part of.
        $stranger = $this->golferIn(League::factory()->create());

        $this->actingAs($admin)
            ->post(route('golfers.store'), ['golfers' => [['golfer_id' => $stranger->id]]])
            ->assertRedirect();

        $this->assertDatabaseMissing('league_user', ['user_id' => $stranger->id, 'league_id' => $current->id]);
    }

    public function test_new_golfer_with_matching_email_attaches_the_existing_person(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);

        $other = League::factory()->create();
        $other->members()->attach($admin->id, ['role' => 'player']);
        $existing = $this->golferIn($other, ['email' => 'dup@example.com']);

        $this->actingAs($admin)
            ->post(route('golfers.store'), [
                'golfers' => [['first_name' => 'Different', 'last_name' => 'Name', 'email' => 'dup@example.com']],
            ])
            ->assertRedirect();

        // No duplicate created; the existing person joins the current league.
        $this->assertSame(1, User::whereNull('password')->count());
        $this->assertDatabaseHas('league_user', ['user_id' => $existing->id, 'league_id' => $current->id]);
    }

    public function test_search_finds_an_outside_account_by_exact_email_only(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);
        // A self-registered account in no league of the admin's.
        $outsider = User::factory()->create([
            'first_name' => 'tester', 'last_name' => 'tester', 'email' => 'tuser@gmail.com',
        ]);

        // A partial term must NOT reveal the outside account (no enumeration).
        $this->actingAs($admin)
            ->getJson(route('golfers.search', ['q' => 'tuse']))
            ->assertOk()
            ->assertJsonCount(0, 'golfers');

        // The full, exact email links the existing account.
        $this->actingAs($admin)
            ->getJson(route('golfers.search', ['q' => 'tuser@gmail.com']))
            ->assertOk()
            ->assertJsonCount(1, 'golfers')
            ->assertJsonPath('golfers.0.id', $outsider->id)
            ->assertJsonPath('golfers.0.external', true);
    }

    public function test_adding_links_an_outside_account_by_exact_email(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);
        $outsider = User::factory()->create(['email' => 'tuser@gmail.com']);

        $this->actingAs($admin)
            ->post(route('golfers.store'), [
                'golfers' => [['golfer_id' => $outsider->id, 'email' => 'tuser@gmail.com']],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('league_user', ['user_id' => $outsider->id, 'league_id' => $current->id]);
    }

    public function test_linking_an_outside_account_requires_the_matching_email(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);
        $outsider = User::factory()->create(['email' => 'tuser@gmail.com']);

        // golfer_id alone (no email) can't attach an outside account.
        $this->actingAs($admin)
            ->post(route('golfers.store'), [
                'golfers' => [['golfer_id' => $outsider->id]],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('league_user', ['user_id' => $outsider->id, 'league_id' => $current->id]);
    }

    public function test_new_golfer_email_dedups_against_an_outside_account(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);
        $outsider = User::factory()->create(['email' => 'tuser@gmail.com']);
        $before = User::count();

        $this->actingAs($admin)
            ->post(route('golfers.store'), [
                'golfers' => [['first_name' => 'New', 'last_name' => 'Person', 'email' => 'tuser@gmail.com']],
            ])
            ->assertRedirect();

        // No duplicate created; the existing account is attached.
        $this->assertSame($before, User::count());
        $this->assertDatabaseHas('league_user', ['user_id' => $outsider->id, 'league_id' => $current->id]);
    }

    public function test_search_returns_in_scope_golfers_excluding_current_league_members(): void
    {
        $current = League::factory()->create();
        $admin = $this->adminOf($current);
        $other = League::factory()->create();
        $other->members()->attach($admin->id, ['role' => 'player']);

        $reusable = $this->golferIn($other, ['first_name' => 'tiger', 'last_name' => 'woods']);
        $this->golferIn($current, ['first_name' => 'tiger', 'last_name' => 'current']); // already in current → excluded
        $this->golferIn(League::factory()->create(), ['first_name' => 'tiger', 'last_name' => 'stranger']); // out of scope

        $this->actingAs($admin)
            ->getJson(route('golfers.search', ['q' => 'tiger']))
            ->assertOk()
            ->assertJsonCount(1, 'golfers')
            ->assertJsonPath('golfers.0.id', $reusable->id)
            ->assertJsonPath('golfers.0.via', $other->name);
    }

    public function test_search_requires_at_least_three_characters(): void
    {
        $league = League::factory()->create();

        $this->actingAs($this->adminOf($league))
            ->getJson(route('golfers.search', ['q' => 'ti']))
            ->assertOk()
            ->assertExactJson(['golfers' => []]);
    }

    public function test_non_admin_cannot_add_or_search_golfers(): void
    {
        $league = League::factory()->create();
        $player = $this->playerOf($league);

        $this->actingAs($player)
            ->post(route('golfers.store'), ['golfers' => [['first_name' => 'No', 'last_name' => 'Way']]])
            ->assertForbidden();

        $this->actingAs($player)
            ->getJson(route('golfers.search', ['q' => 'tiger']))
            ->assertForbidden();
    }

    public function test_admin_can_update_a_golfer_in_their_league(): void
    {
        $league = League::factory()->create();
        // Seeded with a computed index, so the established index isn't required.
        $golfer = $this->golferIn($league, [], 14.0);

        $this->actingAs($this->adminOf($league))
            ->put(route('golfers.update', $golfer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $golfer->id,
            'first_name' => 'jane',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_the_established_index_is_optional(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league); // no rounds -> no computed index

        // Saving without an established index is allowed; it stays unset.
        $this->actingAs($this->adminOf($league))
            ->put(route('golfers.update', $golfer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNull($golfer->fresh()->manual_handicap_index);

        // A seed still stores when provided.
        $this->actingAs($this->adminOf($league))
            ->put(route('golfers.update', $golfer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'manual_handicap_index' => 12.3,
            ])
            ->assertRedirect();

        $this->assertSame(12.3, (float) $golfer->fresh()->manual_handicap_index);
    }

    public function test_the_established_index_is_locked_once_a_golfer_has_a_computed_index(): void
    {
        $league = League::factory()->create();
        $golfer = $this->golferIn($league, [], 14.0); // computed index exists

        // The field is locked: a submitted value is ignored, not stored.
        $this->actingAs($this->adminOf($league))
            ->put(route('golfers.update', $golfer), [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'manual_handicap_index' => 5.0,
            ])
            ->assertRedirect();

        $this->assertNull($golfer->fresh()->manual_handicap_index);
    }

    public function test_admin_cannot_touch_a_golfer_from_another_league(): void
    {
        $league = League::factory()->create();
        $otherGolfer = $this->golferIn(League::factory()->create(), [], 10.0);

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

        // Login-less roster user had no other leagues -> fully removed, with its rounds.
        $this->assertDatabaseMissing('users', ['id' => $golfer->id]);
        $this->assertDatabaseMissing('rounds', ['user_id' => $golfer->id]);
        $this->assertDatabaseMissing('league_user', ['user_id' => $golfer->id]);
    }

    public function test_removing_a_login_user_detaches_but_keeps_the_account(): void
    {
        $league = League::factory()->create();

        // A login-capable member (has a password), not just a roster golfer.
        $member = User::factory()->create();
        $member->leagues()->attach($league->id, ['role' => 'player']);

        $this->actingAs($this->adminOf($league))
            ->delete(route('golfers.destroy', $member))
            ->assertRedirect();

        // Detached from the league, but the login account survives.
        $this->assertDatabaseMissing('league_user', ['user_id' => $member->id, 'league_id' => $league->id]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    public function test_removing_the_league_owner_dissolves_the_league_and_keeps_rounds(): void
    {
        $league = League::factory()->create();
        $owner = $this->adminOf($league);
        $league->update(['owner_id' => $owner->id]);

        // Another golfer, plus rounds for both, all in this league.
        $golfer = $this->golferIn($league);
        $ownerRound = $this->roundFor($owner, $league);
        $golferRound = $this->roundFor($golfer, $league);

        $this->actingAs($owner)
            ->delete(route('golfers.destroy', $owner))
            ->assertRedirect(route('leagues'));

        // The league and every membership are gone.
        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id]);

        // No users are deleted, and both rounds survive as casual (league_id null).
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('users', ['id' => $golfer->id]);
        $this->assertDatabaseHas('rounds', ['id' => $ownerRound->id, 'league_id' => null]);
        $this->assertDatabaseHas('rounds', ['id' => $golferRound->id, 'league_id' => null]);

        // The owner is no longer parked on the deleted league.
        $this->assertNull($owner->fresh()->current_league_id);
    }

    public function test_a_non_owner_admin_cannot_dissolve_the_league_by_removing_the_owner(): void
    {
        $league = League::factory()->create();
        $owner = $this->adminOf($league);
        $league->update(['owner_id' => $owner->id]);

        // A second admin who did not create the league.
        $otherAdmin = User::factory()->create(['current_league_id' => $league->id]);
        $league->members()->attach($otherAdmin->id, ['role' => 'admin']);

        $this->actingAs($otherAdmin)
            ->delete(route('golfers.destroy', $owner))
            ->assertForbidden();

        // The league and the owner's membership are untouched.
        $this->assertDatabaseHas('leagues', ['id' => $league->id]);
        $this->assertDatabaseHas('league_user', ['user_id' => $owner->id, 'league_id' => $league->id]);
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

        // The roster is every league member: the 2 golfers + the acting admin
        // (who, like the real admins, is a member). Ordered by round count, the
        // roundless admin sorts last, so the busy golfer stays at index 0.
        $this->actingAs($this->adminOf($league))
            ->get(route('golfers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Golfers/Index')
                ->has('golfers', 3)
                ->where('golfers.0.id', $busy->id)
                ->where('golfers.0.number_of_rounds', 3)
            );
    }

    public function test_roster_is_cached_and_invalidated_when_it_changes(): void
    {
        $league = League::factory()->create();
        $admin = $this->adminOf($league);

        // A cold read caches the roster (just the admin member so far).
        $this->actingAs($admin)
            ->get(route('golfers.index'))
            ->assertInertia(fn (Assert $page) => $page->has('golfers', 1));
        $this->assertTrue(Cache::has($league->rosterCacheKey()));

        // Adding a golfer must bust the cache so the next read reflects it.
        $this->actingAs($admin)
            ->post(route('golfers.store'), [
                'golfers' => [['first_name' => 'New', 'last_name' => 'Golfer']],
            ])
            ->assertRedirect();
        $this->assertFalse(Cache::has($league->rosterCacheKey()));

        $this->actingAs($admin)
            ->get(route('golfers.index'))
            ->assertInertia(fn (Assert $page) => $page->has('golfers', 2));
    }
}
