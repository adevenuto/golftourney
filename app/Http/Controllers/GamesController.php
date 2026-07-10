<?php

namespace App\Http\Controllers;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\PlayerFinished;
use App\Events\PlayerJoined;
use App\Events\PlayerLeft;
use App\Events\PlayerReopened;
use App\Events\ScoreUpdated;
use App\Http\Requests\JoinGameRequest;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameScoreRequest;
use App\Models\Course;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GamesController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Start a new game — pick a catalog course + teebox; the course context is
     * snapshotted onto the game (mirrors a casual round). The creator is the
     * owner and the first player. Casual only (no league).
     */
    public function store(StoreGameRequest $request): RedirectResponse
    {
        $actor = $request->user();

        // One ongoing game at a time — send them back to it rather than pile up.
        if ($ongoing = Game::ongoingForUser($actor)) {
            return redirect()->route('games.show', $ongoing)
                ->with('error', 'Finish or cancel your current game before starting another.');
        }

        $course = Course::findOrFail($request->integer('course_id'));
        $teebox = is_string($t = $request->input('teebox')) ? $t : null;

        $context = $course->teeboxContext($teebox);
        abort_if(is_null($context), 422, 'That course has no usable tee data.');

        $game = Game::create($context + [
            'owner_id' => $actor->id,
            'course_id' => $course->id,
            'teebox' => $teebox,
            'hole_pars' => $course->holePars($teebox) ?: null,
            'hole_lengths' => $course->holeLengths($teebox) ?: null,
            'status' => Game::STATUS_LOBBY,
        ]);

        $game->players()->create(['user_id' => $actor->id]);

        return redirect()->route('games.show', $game);
    }

    /**
     * The player's games hub — start/join + a list of active and recent games.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $ongoing = Game::ongoingForUser($user);

        return Inertia::render('Games/Index', [
            'games' => Game::listForUser($user),
            'ongoing' => $ongoing ? [
                'id' => $ongoing->id,
                'status' => $ongoing->status,
                'course_name' => $ongoing->courseLabel(),
            ] : null,
        ]);
    }

    /**
     * The live scorecard for a game. A non-player who opens a share link gets a
     * "join this game?" confirmation while the game is still open; otherwise
     * they're sent back to the hub.
     */
    public function show(Request $request, Game $game): Response|RedirectResponse
    {
        if (! $this->isPlayer($game, $request->user()->id)) {
            if ($game->isLobby() && ! $game->isFull()) {
                return Inertia::render('Games/Join', ['game' => $this->joinSummary($game)]);
            }

            return redirect()->route('games.index');
        }

        return Inertia::render('Games/Show', [
            'game' => $this->gamePayload($game),
        ]);
    }

    /**
     * Join a game by its share code (while it's still in the lobby, not full).
     */
    public function join(JoinGameRequest $request): RedirectResponse
    {
        $code = mb_strtoupper(trim((string) $request->input('join_code')));
        $game = Game::where('join_code', $code)->first();
        $userId = $request->user()->id;

        // Surface problems as a field error on the join form (not an exception).
        if ($game === null) {
            throw ValidationException::withMessages(['join_code' => 'We couldn’t find a game with that code.']);
        }

        if (! $this->isPlayer($game, $userId)) {
            if (! $game->isLobby()) {
                throw ValidationException::withMessages(['join_code' => 'This game has already started.']);
            }
            if ($game->isFull()) {
                throw ValidationException::withMessages(['join_code' => 'This game is full.']);
            }
            if (Game::ongoingForUser($request->user()) !== null) {
                throw ValidationException::withMessages(['join_code' => 'Finish or cancel your current game before joining another.']);
            }

            $game->players()->create(['user_id' => $userId]);

            broadcast(new PlayerJoined($game->id, $this->playerSummary($request->user())))->toOthers();
        }

        return redirect()->route('games.show', $game);
    }

    /**
     * A non-host player leaves the game from the lobby (before it starts), in
     * case something comes up. The host cancels the whole game instead.
     */
    public function leave(Request $request, Game $game): RedirectResponse
    {
        $userId = $request->user()->id;

        abort_if($game->owner_id === $userId, 403, 'The host cancels the game instead of leaving.');
        abort_unless($game->isLobby(), 422, 'You can only leave before the game starts.');

        $player = $game->players()->where('user_id', $userId)->first();
        abort_if($player === null, 403);

        $player->delete();

        broadcast(new PlayerLeft($game->id, $userId))->toOthers();

        return redirect()->route('games.index')->with('success', 'You left the game.');
    }

    /**
     * Enter/clear the acting player's strokes for one hole. Own-scores-only: the
     * endpoint always writes the actor's own row, so a player can never edit
     * another's card (no user_id is accepted).
     */
    public function updateScore(UpdateGameScoreRequest $request, Game $game): HttpResponse
    {
        $actor = $request->user();
        abort_unless($this->isPlayer($game, $actor->id), 403);
        abort_unless($game->isActive(), 422, 'Scores can only be entered while the game is active.');

        $hole = $request->integer('hole');
        $strokes = $this->nullableInt($request->input('strokes'));
        $putts = $this->nullableInt($request->input('putts'));

        $game->scores()->updateOrCreate(
            ['user_id' => $actor->id, 'hole' => $hole],
            ['strokes' => $strokes, 'putts' => $putts],
        );

        // Push the new cell to the other players (the editor already has it).
        broadcast(new ScoreUpdated($game->id, $actor->id, $hole, $strokes, $putts, $game->grossFor($actor)))->toOthers();

        // Silent 204 — the client updates optimistically, so no Inertia reload.
        return response()->noContent();
    }

    /**
     * Owner starts the game. A host can start solo (playing alone) or once
     * others have joined — there's no minimum beyond the host. Scores open
     * once active.
     */
    public function start(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_unless($game->isLobby(), 422, 'This game has already started.');

        $game->update(['status' => Game::STATUS_ACTIVE, 'started_at' => now()]);

        broadcast(new GameStarted($game->id))->toOthers();

        return back();
    }

    /**
     * A player finishes their own round — this only marks them done and shows
     * them the results screen; their round isn't posted yet. Rounds are posted
     * for everyone once the game completes, so a player can still reopen their
     * card (see reopen()) and fix an incomplete hole until then.
     */
    public function finish(Request $request, Game $game): RedirectResponse
    {
        $actor = $request->user();
        $player = $game->players()->where('user_id', $actor->id)->first();

        abort_if($player === null, 403);
        abort_unless($game->isActive(), 422, 'You can only finish an active game.');
        abort_if(! is_null($player->finished_at), 422, 'You have already finished this round.');
        abort_if($game->grossFor($actor) < 1, 422, 'Enter your scores before finishing.');

        $scored = [];
        $allFinished = false;

        DB::transaction(function () use ($game, $player, &$scored, &$allFinished): void {
            $player->update(['finished_at' => now()]);

            // The last one out completes the game (and posts everyone's rounds).
            $allFinished = $game->players()->whereNull('finished_at')->doesntExist();
            if ($allFinished) {
                $scored = $this->completeGame($game);
            }
        });

        foreach ($scored as $player) {
            $this->handicaps->recalculateFor($player); // outside the tx; rounds are committed
        }

        if ($allFinished) {
            broadcast(new GameCompleted($game->id))->toOthers();

            return redirect()->route('games.show', $game)->with('success', 'Game finished — rounds posted.');
        }

        broadcast(new PlayerFinished($game->id, $actor->id, $game->grossFor($actor)))->toOthers();

        return redirect()->route('games.show', $game)->with('success', 'Nice round! Waiting on the others.');
    }

    /**
     * A player reopens their own card after finishing (to complete or fix a
     * hole), as long as the game hasn't fully completed. No round has been
     * posted yet, so this just puts them back into play.
     */
    public function reopen(Request $request, Game $game): RedirectResponse
    {
        $actor = $request->user();
        $player = $game->players()->where('user_id', $actor->id)->first();

        abort_if($player === null, 403);
        abort_unless($game->isActive(), 422, 'This game is already finished.');
        abort_if(is_null($player->finished_at), 422, 'You haven’t finished yet.');

        $player->update(['finished_at' => null]);

        broadcast(new PlayerReopened($game->id, $actor->id))->toOthers();

        return redirect()->route('games.show', $game);
    }

    /**
     * Owner ends the game for everyone (a fallback when someone won't finish on
     * their own): mark all remaining players finished and complete the game.
     */
    public function finalize(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_unless($game->isActive(), 422, 'Only an active game can be finished.');

        $scored = [];

        DB::transaction(function () use ($game, &$scored): void {
            $game->players()->whereNull('finished_at')->update(['finished_at' => now()]);
            $scored = $this->completeGame($game);
        });

        // Recompute outside the transaction (busts caches; rounds are committed).
        foreach ($scored as $player) {
            $this->handicaps->recalculateFor($player);
        }

        broadcast(new GameCompleted($game->id))->toOthers();

        return redirect()->route('games.show', $game)->with('success', 'Game finished — rounds posted.');
    }

    /**
     * Complete a game: post a casual Round for each player (gross = sum of their
     * holes, snapshotting the game's course context) and mark it completed.
     * Idempotent per player via game_players.round_id.
     *
     * @return list<User> the players whose handicap needs recomputing
     */
    private function completeGame(Game $game): array
    {
        $scored = [];

        foreach ($game->players()->with('user')->get() as $player) {
            if ($player->round_id) {
                continue; // already posted
            }

            $gross = $game->grossFor($player->user);
            if ($gross < 1) {
                continue; // entered no scores — no round
            }

            $round = $player->user->rounds()->create([
                'league_id' => null,
                'course_id' => $game->course_id,
                'teebox' => $game->teebox,
                'course_rating' => $game->course_rating,
                'slope_rating' => $game->slope_rating,
                'par' => $game->par,
                'holes' => $game->holes,
                'score' => $gross,
                'created_at' => now(),
            ]);

            $player->update(['round_id' => $round->id]);
            $scored[] = $player->user;
        }

        $game->update(['status' => Game::STATUS_COMPLETED, 'completed_at' => now()]);

        return $scored;
    }

    /**
     * Owner cancels a game before it's finished. Canceled games aren't kept —
     * the game (and its players/scores) is deleted, so it never lingers in a
     * player's list. No rounds are created.
     */
    public function abandon(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_if($game->isCompleted(), 422, 'A finished game can’t be canceled.');

        $game->delete(); // cascades players + scores

        return redirect()->route('games.index')->with('success', 'Game canceled.');
    }

    /**
     * Whether the user is a player in the game.
     */
    private function isPlayer(Game $game, int $userId): bool
    {
        return $game->players()->where('user_id', $userId)->exists();
    }

    /**
     * A submitted score field as a nullable int (blank/empty ⇒ null).
     */
    private function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    /**
     * Minimal game info for the share-link "join this game?" screen.
     *
     * @return array<string, mixed>
     */
    private function joinSummary(Game $game): array
    {
        $game->load('owner:id,first_name,last_name', 'course:id,club_name,course_name');

        return [
            'id' => $game->id,
            'join_code' => $game->join_code,
            'course_name' => $game->courseLabel(),
            'holes' => $game->holes,
            'teebox' => $game->teebox,
            'host' => trim($game->owner->first_name.' '.$game->owner->last_name),
            'players_count' => $game->players()->count(),
        ];
    }

    /**
     * A fresh player's card entry, matching the shape used in gamePayload().
     *
     * @return array<string, mixed>
     */
    private function playerSummary(User $user): array
    {
        return [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_owner' => false,
            'confirmed' => false,
            'finished' => false,
            'holes' => [],
            'putts' => [],
            'gross' => 0,
        ];
    }

    /**
     * The full scorecard payload for the frontend.
     *
     * @return array<string, mixed>
     */
    private function gamePayload(Game $game): array
    {
        $game->load(['players.user:id,first_name,last_name', 'scores']);

        $scoresByUser = $game->scores->groupBy('user_id');

        $players = $game->players->map(function (GamePlayer $p) use ($game, $scoresByUser): array {
            $rows = $scoresByUser->get($p->user_id, collect());
            $holes = $rows->pluck('strokes', 'hole');

            return [
                'user_id' => $p->user_id,
                'first_name' => $p->user->first_name,
                'last_name' => $p->user->last_name,
                'is_owner' => $p->user_id === $game->owner_id,
                'confirmed' => ! is_null($p->confirmed_at),
                'finished' => ! is_null($p->finished_at),
                'holes' => $holes->all(),
                'putts' => $rows->pluck('putts', 'hole')->all(),
                'gross' => (int) $holes->filter(fn ($s) => ! is_null($s))->sum(),
            ];
        })->values()->all();

        return [
            'id' => $game->id,
            'status' => $game->status,
            'join_code' => $game->join_code,
            'holes' => $game->holes,
            'hole_numbers' => $game->holeNumbers(),
            'par' => $game->par,
            'hole_pars' => $game->hole_pars ?? [],
            'hole_lengths' => $game->hole_lengths ?? [],
            'teebox' => $game->teebox,
            'course_name' => $game->courseLabel(),
            'course_sub' => $game->courseSubLabel(),
            'owner_id' => $game->owner_id,
            'players' => $players,
        ];
    }
}
