<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Round;
use App\Services\HandicapService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyHandicapController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * The signed-in player's own, league-agnostic handicap page: their portable
     * Index and full round history (every league + casual), where they manage
     * their own casual rounds.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('MyHandicap', [
            'index' => $this->handicaps->formatIndex($user->effectiveHandicapIndex()),
            'userId' => $user->id,
            'recentWindow' => $this->handicaps->recentWindowSize($user),
            // The leagues this player can attribute a round to (plus casual).
            'leagues' => $user->leagues()
                ->orderBy('name')
                ->get()
                ->map(fn ($l): array => ['id' => $l->id, 'name' => $l->name])
                ->all(),
            'rounds' => $user->rounds()
                ->with(['league:id,name', 'course:id,club_name,course_name'])
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Round $r): array => [
                    'id' => $r->id,
                    'score' => $r->score,
                    'created_at' => $r->created_at,
                    'origin' => $r->originLabel(),
                    'is_casual' => is_null($r->league_id),
                ]),
            'usedRoundIds' => $this->handicaps->usedRoundIds($user),
            // Live games the player is in (active/waiting/recent) for the hub card.
            'games' => Game::listForUser($user),
        ]);
    }
}
