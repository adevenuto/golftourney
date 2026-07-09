<?php

namespace App\Http\Controllers;

use App\Events\GameCompleted;
use App\Events\GameStarted;
use App\Events\PlayerJoined;
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
        $course = Course::findOrFail($request->integer('course_id'));
        $teebox = is_string($t = $request->input('teebox')) ? $t : null;

        $context = $course->teeboxContext($teebox);
        abort_if(is_null($context), 422, 'That course has no usable tee data.');

        $game = Game::create($context + [
            'owner_id' => $actor->id,
            'course_id' => $course->id,
            'teebox' => $teebox,
            'hole_pars' => $course->holePars($teebox) ?: null,
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
        return Inertia::render('Games/Index', [
            'games' => Game::listForUser($request->user()),
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
        $game = Game::where('join_code', mb_strtoupper(trim((string) $request->input('join_code'))))->firstOrFail();
        $userId = $request->user()->id;

        if (! $this->isPlayer($game, $userId)) {
            abort_unless($game->isLobby(), 422, 'This game has already started.');
            abort_if($game->isFull(), 422, 'This game is full.');

            $game->players()->create(['user_id' => $userId]);

            broadcast(new PlayerJoined($game->id, $this->playerSummary($request->user())))->toOthers();
        }

        return redirect()->route('games.show', $game);
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
     * Owner starts the game (needs at least MIN_PLAYERS). Scores open once active.
     */
    public function start(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_unless($game->isLobby(), 422, 'This game has already started.');
        abort_unless($game->players()->count() >= Game::MIN_PLAYERS, 422, 'Need at least two players to start.');

        $game->update(['status' => Game::STATUS_ACTIVE, 'started_at' => now()]);

        broadcast(new GameStarted($game->id))->toOthers();

        return back();
    }

    /**
     * Owner finishes the game: post a casual Round per player (gross = sum of
     * their holes, snapshotting the game's course context), then recompute each
     * player's handicap. Idempotent via game_players.round_id.
     */
    public function finalize(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_unless($game->isActive(), 422, 'Only an active game can be finished.');

        $context = [
            'course_rating' => $game->course_rating,
            'slope_rating' => $game->slope_rating,
            'par' => $game->par,
            'holes' => $game->holes,
        ];

        $scored = [];

        DB::transaction(function () use ($game, $context, &$scored): void {
            foreach ($game->players()->with('user')->get() as $player) {
                if ($player->round_id) {
                    continue; // already posted
                }

                $gross = $game->grossFor($player->user);
                if ($gross < 1) {
                    continue; // player entered no scores — skip
                }

                $round = $player->user->rounds()->create($context + [
                    'league_id' => null,
                    'course_id' => $game->course_id,
                    'teebox' => $game->teebox,
                    'score' => $gross,
                    'created_at' => now(),
                ]);

                $player->update(['round_id' => $round->id]);
                $scored[] = $player->user;
            }

            $game->update(['status' => Game::STATUS_COMPLETED, 'completed_at' => now()]);
        });

        // Recompute outside the transaction (busts caches; rounds are committed).
        foreach ($scored as $player) {
            $this->handicaps->recalculateFor($player);
        }

        broadcast(new GameCompleted($game->id))->toOthers();

        return redirect()->route('games.show', $game)->with('success', 'Game finished — rounds posted.');
    }

    /**
     * Owner cancels a game before it's finished — no rounds are created.
     */
    public function abandon(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->owner_id === $request->user()->id, 403);
        abort_if($game->isCompleted(), 422, 'A finished game can’t be canceled.');

        $game->update(['status' => Game::STATUS_ABANDONED]);

        return redirect()->route('my-handicap')->with('success', 'Game canceled.');
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
            'teebox' => $game->teebox,
            'course_name' => $game->courseLabel(),
            'course_sub' => $game->courseSubLabel(),
            'owner_id' => $game->owner_id,
            'players' => $players,
        ];
    }
}
