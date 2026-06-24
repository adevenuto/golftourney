<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoundRequest;
use App\Http\Requests\UpdateRoundRequest;
use App\Models\League;
use App\Models\Round;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoundsController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Show a golfer's rounds within the current league.
     */
    public function index(Request $request, User $user): Response
    {
        $league = $this->leagueFor($request, $user);

        return Inertia::render('Rounds/Index', [
            'golfer' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'index' => $this->handicaps->formatIndex($user->effectiveHandicapIndex()),
                'course_handicap' => $this->handicaps->courseHandicap($user, $league),
            ],
            'rounds' => $user->rounds()
                ->where('league_id', $league->id)
                ->orderByDesc('created_at')
                ->get(),
            'usedRoundIds' => $this->handicaps->usedRoundIds($user),
        ]);
    }

    /**
     * Store a new round for the golfer in the current league.
     */
    public function store(StoreRoundRequest $request, User $user): RedirectResponse
    {
        $league = $this->leagueFor($request, $user);

        $user->rounds()->create([
            'league_id' => $league->id,
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round added.');
    }

    /**
     * Update a round.
     */
    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $this->leagueForRound($request, $round); // authorize: round is in the current league

        $round->update([
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($round->user);

        return back()->with('success', 'Round updated.');
    }

    /**
     * Delete a round.
     */
    public function destroy(Request $request, Round $round): RedirectResponse
    {
        $this->leagueForRound($request, $round); // authorize: round is in the current league
        $user = $round->user;
        $round->delete();

        $this->handicaps->recalculateFor($user);

        return back()->with('success', 'Round removed.');
    }

    /**
     * Resolve the acting user's current league, ensuring the golfer belongs to it.
     */
    private function leagueFor(Request $request, User $user): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $user->leagues()->whereKey($league->id)->exists(), 404);

        return $league;
    }

    /**
     * Resolve the current league, ensuring the round belongs to it.
     */
    private function leagueForRound(Request $request, Round $round): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $round->league_id === $league->id, 404);

        return $league;
    }
}
