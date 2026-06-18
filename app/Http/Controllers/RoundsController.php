<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoundRequest;
use App\Http\Requests\UpdateRoundRequest;
use App\Models\Golfer;
use App\Models\League;
use App\Models\Round;
use App\Services\HandicapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index(Request $request, Golfer $golfer): Response
    {
        $league = $this->leagueFor($request, $golfer);

        $handicap = DB::table('golfer_league')
            ->where('golfer_id', $golfer->id)
            ->where('league_id', $league->id)
            ->value('handicap');

        return Inertia::render('Rounds/Index', [
            'golfer' => [
                'id' => $golfer->id,
                'first_name' => $golfer->first_name,
                'last_name' => $golfer->last_name,
                'handicap' => $handicap,
            ],
            'rounds' => $golfer->rounds()
                ->where('league_id', $league->id)
                ->orderByDesc('created_at')
                ->get(),
            'countingRoundIds' => $this->handicaps->countingRounds($golfer, $league)->pluck('id'),
        ]);
    }

    /**
     * Store a new round for the golfer in the current league.
     */
    public function store(StoreRoundRequest $request, Golfer $golfer): RedirectResponse
    {
        $league = $this->leagueFor($request, $golfer);

        $golfer->rounds()->create([
            'league_id' => $league->id,
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($golfer, $league);

        return back()->with('success', 'Round added.');
    }

    /**
     * Update a round.
     */
    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $league = $this->leagueForRound($request, $round);

        $round->update([
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($round->golfer, $league);

        return back()->with('success', 'Round updated.');
    }

    /**
     * Delete a round.
     */
    public function destroy(Request $request, Round $round): RedirectResponse
    {
        $league = $this->leagueForRound($request, $round);
        $golfer = $round->golfer;
        $round->delete();

        $this->handicaps->recalculateFor($golfer, $league);

        return back()->with('success', 'Round removed.');
    }

    /**
     * Resolve the acting user's current league, ensuring the golfer belongs to it.
     */
    private function leagueFor(Request $request, Golfer $golfer): League
    {
        $league = $request->user()->currentLeague;
        abort_unless($league && $golfer->leagues()->whereKey($league->id)->exists(), 404);

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
