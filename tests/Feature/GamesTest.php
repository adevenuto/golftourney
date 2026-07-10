<?php

namespace Tests\Feature;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\PlayerFinished;
use App\Events\PlayerJoined;
use App\Events\PlayerLeft;
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
                    'holes' => collect(range(1, 18))->mapWithKeys(fn ($i) => ['hole-'.$i => ['par' => '4', 'length' => '400']])->all(),
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

        // Per-hole par + length are snapshotted (18 holes; par 4 / 400 yds).
        $this->assertCount(18, $game->hole_pars);
        $this->assertSame(4, $game->hole_pars[1]);
        $this->assertCount(18, $game->hole_lengths);
        $this->assertSame(400, $game->hole_lengths[1]);
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

    public function test_a_non_host_player_can_leave_the_lobby_but_the_host_cannot(): void
    {
        Event::fake([PlayerLeft::class]);

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);

        // The host can't "leave" — they cancel the game instead.
        $this->actingAs($owner)->post(route('games.leave', $game))->assertForbidden();

        // A non-host player leaves and is dropped from the roster.
        $this->actingAs($other)->post(route('games.leave', $game))->assertRedirect(route('games.index'));
        $this->assertDatabaseMissing('game_players', ['game_id' => $game->id, 'user_id' => $other->id]);
        $this->assertDatabaseHas('game_players', ['game_id' => $game->id, 'user_id' => $owner->id]);
        Event::assertDispatched(PlayerLeft::class);

        // Can't leave once the game has started.
        $game->players()->create(['user_id' => $other->id]);
        $game->update(['status' => Game::STATUS_ACTIVE]);
        $this->actingAs($other)->post(route('games.leave', $game))->assertStatus(422);
    }

    public function test_cannot_join_a_full_game(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner, ...User::factory()->count(3)->create()); // 4 players = full

        $this->actingAs(User::factory()->create())
            ->post(route('games.join'), ['join_code' => $game->join_code])
            ->assertInvalid(['join_code' => 'This game is full.']);
    }

    public function test_joining_a_started_or_unknown_game_fails_gracefully(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        // An already-started game → a friendly field error, not an exception.
        $this->actingAs(User::factory()->create())
            ->post(route('games.join'), ['join_code' => $game->join_code])
            ->assertInvalid(['join_code' => 'This game has already started.']);

        // An unknown code → a friendly field error, not a 404.
        $this->actingAs(User::factory()->create())
            ->post(route('games.join'), ['join_code' => 'NOPECODE'])
            ->assertInvalid(['join_code']);
    }

    public function test_the_owner_can_start_the_game_solo_and_others_cannot_start_it(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner); // just the owner

        // A non-owner can't start it.
        $this->actingAs($other)->post(route('games.start', $game))->assertForbidden();

        // The owner can start solo — no minimum beyond the host.
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

    public function test_a_player_can_record_putts_alongside_strokes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $this->actingAs($owner)
            ->patch(route('games.scores.update', $game), ['hole' => 1, 'strokes' => 4, 'putts' => 2])
            ->assertNoContent();

        $this->assertDatabaseHas('game_scores', [
            'game_id' => $game->id, 'user_id' => $owner->id, 'hole' => 1, 'strokes' => 4, 'putts' => 2,
        ]);
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
        // Ending for everyone marks all players finished.
        $this->assertSame(0, $game->players()->whereNull('finished_at')->count());

        // Re-finalizing a completed game is rejected and creates no extra rounds.
        $this->actingAs($owner)->post(route('games.finalize', $game))->assertStatus(422);
        $this->assertDatabaseCount('rounds', 2);
    }

    public function test_each_player_finishes_their_own_round_and_rounds_post_when_all_are_done(): void
    {
        Event::fake([PlayerFinished::class, GameCompleted::class]);

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        foreach ([4, 5, 3] as $i => $s) { // owner gross 12
            $game->scores()->create(['user_id' => $owner->id, 'hole' => $i + 1, 'strokes' => $s]);
        }
        foreach ([5, 4, 4] as $i => $s) { // other gross 13
            $game->scores()->create(['user_id' => $other->id, 'hole' => $i + 1, 'strokes' => $s]);
        }

        // A non-player can't finish.
        $this->actingAs(User::factory()->create())->post(route('games.finish', $game))->assertForbidden();

        // One player finishes: marked done, but no round is posted yet.
        $this->actingAs($other)->post(route('games.finish', $game))->assertRedirect(route('games.show', $game));
        $this->assertNotNull($game->players()->where('user_id', $other->id)->first()->finished_at);
        $this->assertSame(Game::STATUS_ACTIVE, $game->fresh()->status);
        $this->assertDatabaseCount('rounds', 0);
        Event::assertDispatched(PlayerFinished::class);
        Event::assertNotDispatched(GameCompleted::class);

        // Finishing twice is rejected.
        $this->actingAs($other)->post(route('games.finish', $game))->assertStatus(422);

        // The last player to finish completes the game and posts everyone's round.
        $this->actingAs($owner)->post(route('games.finish', $game))->assertRedirect();
        $this->assertSame(Game::STATUS_COMPLETED, $game->fresh()->status);
        $this->assertDatabaseHas('rounds', ['user_id' => $owner->id, 'league_id' => null, 'score' => 12]);
        $this->assertDatabaseHas('rounds', ['user_id' => $other->id, 'league_id' => null, 'score' => 13]);
        $this->assertDatabaseCount('rounds', 2);
        Event::assertDispatched(GameCompleted::class);
    }

    public function test_a_finished_player_can_reopen_their_card_until_the_game_completes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $game->scores()->create(['user_id' => $owner->id, 'hole' => 1, 'strokes' => 4]);
        $game->scores()->create(['user_id' => $other->id, 'hole' => 1, 'strokes' => 5]);

        // Finish, then reopen — back to playing, still nothing posted.
        $this->actingAs($other)->post(route('games.finish', $game))->assertRedirect();
        $this->actingAs($other)->post(route('games.reopen', $game))->assertRedirect(route('games.show', $game));
        $this->assertNull($game->players()->where('user_id', $other->id)->first()->finished_at);
        $this->assertSame(Game::STATUS_ACTIVE, $game->fresh()->status);
        $this->assertDatabaseCount('rounds', 0);

        // Once everyone finishes and the game completes, reopening is rejected.
        $this->actingAs($other)->post(route('games.finish', $game))->assertRedirect();
        $this->actingAs($owner)->post(route('games.finish', $game))->assertRedirect();
        $this->assertSame(Game::STATUS_COMPLETED, $game->fresh()->status);
        $this->actingAs($other)->post(route('games.reopen', $game))->assertStatus(422);
    }

    public function test_a_player_cannot_finish_without_entering_scores(): void
    {
        $owner = User::factory()->create();
        $game = $this->gameWith($owner, User::factory()->create());
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $this->actingAs($owner)->post(route('games.finish', $game))->assertStatus(422);
        $this->assertDatabaseCount('rounds', 0);
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

    public function test_owner_can_cancel_a_game_which_deletes_it_without_posting_rounds(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $game = $this->gameWith($owner, $other);
        $game->update(['status' => Game::STATUS_ACTIVE]);

        $this->actingAs($other)->post(route('games.abandon', $game))->assertForbidden();

        // Canceling deletes the game (and its players) — canceled games aren't kept.
        $this->actingAs($owner)->post(route('games.abandon', $game))->assertRedirect(route('games.index'));
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        $this->assertDatabaseMissing('game_players', ['game_id' => $game->id]);
        $this->assertDatabaseCount('rounds', 0);
    }

    public function test_a_player_cannot_start_or_join_while_they_have_an_ongoing_game(): void
    {
        $owner = User::factory()->create();
        $ongoing = $this->gameWith($owner);
        $ongoing->update(['status' => Game::STATUS_ACTIVE]);

        // Starting another game bounces back to the one already in progress.
        $this->actingAs($owner)
            ->post(route('games.store'), ['course_id' => $this->course()->id, 'teebox' => 'Blue'])
            ->assertRedirect(route('games.show', $ongoing));
        $this->assertSame(1, Game::count());

        // Joining someone else's game is blocked too.
        $host = User::factory()->create();
        $theirGame = $this->gameWith($host);

        $this->actingAs($owner)
            ->post(route('games.join'), ['join_code' => $theirGame->join_code])
            ->assertInvalid(['join_code']);
        $this->assertDatabaseMissing('game_players', ['game_id' => $theirGame->id, 'user_id' => $owner->id]);
    }

    public function test_canceled_games_do_not_appear_in_a_players_list(): void
    {
        $owner = User::factory()->create();
        $live = $this->gameWith($owner);
        $live->update(['status' => Game::STATUS_ACTIVE]);
        $abandoned = $this->gameWith($owner);
        $abandoned->update(['status' => Game::STATUS_ABANDONED]);

        $list = Game::listForUser($owner);

        $this->assertCount(1, $list);
        $this->assertSame($live->id, $list[0]['id']);
    }
}
