<?php

namespace Tests\Feature;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\PlayerJoined;
use App\Events\ScoreUpdated;
use App\Models\Course;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GamesTest extends TestCase
{
    use RefreshDatabase;

    /** An 18-hole catalog course (par 72, slope 113, rating 72) with a Blue tee. */
    private function course(): Course
    {
        return Course::factory()->create([
            'layout_data' => [
                'hole_count' => 18,
                'teeboxes' => [[
                    'name' => 'Blue',
                    'slope' => 113,
                    'courseRating' => 72.0,
                    'holes' => collect(range(1, 18))->mapWithKeys(fn ($i) => ['hole-'.$i => ['par' => '4']])->all(),
                ]],
            ],
        ]);
    }

    /** A lobby game owned by $owner with the given players attached. */
    private function gameWith(User $owner, User ...$others): Game
    {
        $game = Game::factory()->create(['owner_id' => $owner->id]);
        $game->players()->create(['user_id' => $owner->id]);
        foreach ($others as $u) {
            $game->players()->create(['user_id' => $u->id]);
        }

        return $game;
    }

    public function test_a_user_can_start_a_game_at_a_course(): void
    {
        $user = User::factory()->create();
        $course = $this->course();

        $this->actingAs($user)
            ->post(route('games.store'), ['course_id' => $course->id, 'teebox' => 'Blue'])
            ->assertRedirect();

        $game = Game::firstOrFail();
        $this->assertSame($user->id, $game->owner_id);
        $this->assertSame(18, $game->holes);
        $this->assertNotEmpty($game->join_code);
        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'user_id' => $user->id]);

        // Per-hole par is snapshotted (18 holes, par 4 each in the fixture).
        $this->assertCount(18, $game->hole_pars);
        $this->assertSame(4, $game->hole_pars[1]);
    }

    public function test_a_player_sees_the_scorecard(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner);

        $this->actingAs($owner)
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Games/Show'));
    }

    public function test_a_non_player_is_offered_to_join_an_open_game_or_redirected(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner); // lobby, not full

        // Share-link visitor on an open game → join confirmation.
        $this->actingAs(User::factory()->create())
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Games/Join'));

        // Once it's no longer joinable → back to the hub.
        $game->update(['status' => Game::STATUS_ACTIVE]);
        $this->actingAs(User::factory()->create())
            ->get(route('games.show', $game))
            ->assertRedirect(route('games.index'));
    }

    public function test_the_games_hub_lists_the_users_games(): void
    {
        $owner = User::factory()->create();
        $this->gameWith($owner);

        $this->actingAs($owner)
            ->get(route('games.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Games/Index')->has('games', 1));
    }

    public function test_a_user_can_join_by_code(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $game = $this->gameWith($owner);

        $this->actingAs($joiner)
            ->post(route('games.join'), ['join_code' => strtolower($game->join_code)]) // case-insensitive
            ->assertRedirect(route('games.show', $game));

        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'user_id' => $joiner->id]);
    }

    public function test_cannot_join_a_full_game(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner, ...User::factory()->count(3)->create()); // 4 players = full

        $this->actingAs(User::factory()->create())
            ->post(route('games.join'), ['join_code' => $game->join_code])
            ->assertStatus(422);
    }

    public function test_cannot_join_a_started_game(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $this->actingAs(User::factory()->create())
            ->post(route('games.join'), ['join_code' => $game->join_code])
            ->assertStatus(422);
    }

    public function test_owner_starts_the_game_with_enough_players(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner); // just the owner

        // Not enough players yet.
        $this->actingAs($owner)->post(route('games.start', $game))->assertStatus(422);

        $game->players()->create(['user_id' => $other->id]);

        // Non-owner can't start.
        $this->actingAs($other)->post(route('games.start', $game))->assertForbidden();

        // Owner starts it.
        $this->actingAs($owner)->post(route('games.start', $game))->assertRedirect();
        $this->assertSame(Game::STATUS_ACTIVE, $game->fresh()->status);
        $this->assertNotNull($game->fresh()->started_at);
    }

    public function test_a_player_enters_only_their_own_scores_and_only_when_active(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);

        // Not active yet → rejected.
        $this->actingAs($owner)
            ->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 4])
            ->assertStatus(422);

        $game->update(['status' => Game::STATUS_ACTIVE]);

        // A non-player can't post scores.
        $this->actingAs(User::factory()->create())
            ->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 4])
            ->assertForbidden();

        // Owner enters hole 1, then corrects it (upsert, not a duplicate row).
        $this->actingAs($owner)->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 4])->assertNoContent();
        $this->actingAs($owner)->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 5])->assertNoContent();

        $this->assertDatabaseHas('game_scores', ['game_id' => $game->id, 'user_id' => $owner->id, 'hole' => 1, 'strokes' => 5]);
        $this->assertSame(1, $game->scores()->where('user_id', $owner->id)->count());
    }

    public function test_finalizing_posts_a_casual_round_per_player_and_is_guarded(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        // Owner gross 12, other gross 13.
        foreach ([4, 5, 3] as $i => $s) {
            $game->scores()->create(['user_id' => $owner->id, 'hole' => $i + 1, 'strokes' => $s]);
        }
        foreach ([5, 4, 4] as $i => $s) {
            $game->scores()->create(['user_id' => $other->id, 'hole' => $i + 1, 'strokes' => $s]);
        }

        // Non-owner can't finalize.
        $this->actingAs($other)->post(route('games.finalize', $game))->assertForbidden();

        $this->actingAs($owner)->post(route('games.finalize', $game))->assertRedirect();

        $this->assertSame(Game::STATUS_COMPLETED, $game->fresh()->status);
        $this->assertDatabaseHas('rounds', ['user_id' => $owner->id, 'league_id' => null, 'score' => 12]);
        $this->assertDatabaseHas('rounds', ['user_id' => $other->id, 'league_id' => null, 'score' => 13]);
        $this->assertDatabaseCount('rounds', 2);

        // Re-finalizing a completed game is rejected and creates no extra rounds.
        $this->actingAs($owner)->post(route('games.finalize', $game))->assertStatus(422);
        $this->assertDatabaseCount('rounds', 2);
    }

    public function test_realtime_events_are_broadcast_across_the_lifecycle(): void
    {
        Event::fake([PlayerJoined::class, GameStarted::class, ScoreUpdated::class, GameCompleted::class]);

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner);

        $this->actingAs($other)->post(route('games.join'), ['join_code' => $game->join_code])->assertRedirect();
        Event::assertDispatched(PlayerJoined::class);

        $this->actingAs($owner)->post(route('games.start', $game))->assertRedirect();
        Event::assertDispatched(GameStarted::class);

        $this->actingAs($owner)->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 4])->assertNoContent();
        Event::assertDispatched(ScoreUpdated::class);

        $this->actingAs($owner)->post(route('games.finalize', $game))->assertRedirect();
        Event::assertDispatched(GameCompleted::class);
    }

    public function test_owner_can_abandon_a_game_without_posting_rounds(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $this->actingAs($other)->post(route('games.abandon', $game))->assertForbidden();

        $this->actingAs($owner)->post(route('games.abandon', $game))->assertRedirect(route('my-handicap'));
        $this->assertSame(Game::STATUS_ABANDONED, $game->fresh()->status);
        $this->assertDatabaseCount('rounds', 0);
    }
}
