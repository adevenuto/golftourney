<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoundRequest;
use App\Http\Requests\UpdateRoundRequest;
use App\Models\Golfer;
use App\Models\Round;
use App\Services\HandicapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class RoundsController extends Controller
{
    public function __construct(private HandicapService $handicaps)
    {
        $this->middleware('auth');
    }

    /**
     * Return a golfer's counting rounds and total round count.
     */
    public function index(Golfer $golfer): JsonResponse
    {
        return response()->json(['rounds' => [
            'latest' => $this->handicaps->countingRounds($golfer),
            'total' => $golfer->rounds()->count(),
        ]]);
    }

    /**
     * Display the view.
     */
    public function create(): View
    {
        return view('rounds.index');
    }

    /**
     * Store a new golfer round in storage.
     */
    public function store(StoreRoundRequest $request): JsonResponse
    {
        $golfer = Golfer::findOrFail($request->integer('golfer_id'));

        $golfer->rounds()->create([
            'score' => $request->integer('score'),
            'course_name' => 'Robert A. Black',
            'created_at' => $request->date('created_at'),
        ]);

        $this->handicaps->recalculateFor($golfer);

        return response()->json(['success' => 'Round was successfully created'], 201);
    }

    /**
     * Update a round in storage.
     */
    public function edit(UpdateRoundRequest $request): JsonResponse
    {
        $round = Round::findOrFail($request->integer('id'));

        $round->update([
            'score' => $request->integer('score'),
            'created_at' => $request->date('created_at'),
        ]);

        // Recalculate using the round's own golfer, not request input.
        $this->handicaps->recalculateFor($round->golfer);

        return response()->json(['success' => 'Round was successfully updated']);
    }

    /**
     * Delete a round from storage.
     */
    public function delete(Round $round): JsonResponse
    {
        $golfer = $round->golfer;
        $round->delete();

        $this->handicaps->recalculateFor($golfer);

        return response()->json(['success' => 'Round was successfully deleted']);
    }
}
