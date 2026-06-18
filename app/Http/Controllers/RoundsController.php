<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoundRequest;
use App\Http\Requests\UpdateRoundRequest;
use App\Models\Golfer;
use App\Models\Round;
use App\Services\HandicapService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoundsController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Show a golfer's rounds.
     */
    public function index(Golfer $golfer): Response
    {
        return Inertia::render('Rounds/Index', [
            'golfer' => $golfer,
            'rounds' => $golfer->rounds()->orderByDesc('created_at')->get(),
            'countingRoundIds' => $this->handicaps->countingRounds($golfer)->pluck('id'),
        ]);
    }

    /**
     * Store a new round for the golfer.
     */
    public function store(StoreRoundRequest $request, Golfer $golfer): RedirectResponse
    {
        $golfer->rounds()->create([
            'score' => $request->integer('score'),
            'course_name' => 'Robert A. Black',
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($golfer);

        return back()->with('success', 'Round added.');
    }

    /**
     * Update a round.
     */
    public function update(UpdateRoundRequest $request, Round $round): RedirectResponse
    {
        $round->update([
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor(Golfer::findOrFail($round->golfer_id));

        return back()->with('success', 'Round updated.');
    }

    /**
     * Delete a round.
     */
    public function destroy(Round $round): RedirectResponse
    {
        $golfer = Golfer::findOrFail($round->golfer_id);
        $round->delete();

        $this->handicaps->recalculateFor($golfer);

        return back()->with('success', 'Round removed.');
    }
}
