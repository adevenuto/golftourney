<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\Golfer;
use Illuminate\Http\Request;
use App\Traits\HandicapTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;

class RoundsController extends Controller
{
    use HandicapTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Return a golfer's latest rounds and total.
     *
     * @param int $id
     */
    public function index(int $id): JsonResponse
    {
        $golfer = Golfer::findOrFail($id);

        return response()->json(['rounds' => [
            'latest' => $this->latest_rounds($golfer->id),
            'total' => $this->total_rounds($golfer->id),
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
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'golfer_id' => 'required|integer|exists:golfers,id',
            'score' => 'required|integer|min:1|max:150',
            'created_at' => 'required|date',
        ]);

        $round = new Round();
        $round->golfer_id = $validated['golfer_id'];
        $round->score = $validated['score'];
        $round->course_name = 'Robert A. Black';
        $round->created_at = $validated['created_at'];
        $round->save();

        // Recalculate the golfer's handicap from their latest rounds.
        $this->update_golfer_handicap($round->golfer_id);

        return response()->json(['success' => 'Round was successfully created'], 201);
    }

    /**
     * Update a round in storage.
     */
    public function edit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:rounds,id',
            'score' => 'required|integer|min:1|max:150',
            'created_at' => 'required|date',
        ]);

        $round = Round::findOrFail($validated['id']);
        $round->score = $validated['score'];
        $round->created_at = $validated['created_at'];
        $round->save();

        // Recalculate using the round's own golfer, not request input.
        $this->update_golfer_handicap($round->golfer_id);

        return response()->json(['success' => 'Round was successfully updated']);
    }

    /**
     * Delete a round from storage.
     *
     * @param int $id
     */
    public function delete($id): JsonResponse
    {
        $round = Round::findOrFail($id);
        $golferId = $round->golfer_id;
        $round->delete();

        // Recalculate the affected golfer's handicap.
        $this->update_golfer_handicap($golferId);

        return response()->json(['success' => 'Round was successfully deleted']);
    }
}
