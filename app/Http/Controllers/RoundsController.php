<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\Golfer;
use Illuminate\Http\Request;
use App\Traits\HandicapTrait;
use Illuminate\Http\JsonResponse;
use \Illuminate\Contracts\View\View;

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
     * Return golfers latest rounds
     * @param int $id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Int $id): JsonResponse
    {   
        try {
            $golfer = Golfer::find($id);
            $latest_rounds = $this->latest_rounds($golfer->id);
            $total_rounds = $this->total_rounds($golfer->id);
            return response()->json(['rounds' => [
                'latest' => $latest_rounds,
                'total' => $total_rounds
            ]], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the view.
     * 
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('rounds.index');
    }

    /**
     * Store a new golfer round in storage.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $round = new Round();
            $round->golfer_id = intval($request->golfer_id);
            $round->score = intval($request->score);
            $round->course_name = 'Robert A. Black';
            $round->created_at = $request->created_at;
            $round->save();

            // calc new handicap
            $this->update_golfer_handicap($request->golfer_id);

            return response()->json(['success' => 'Round was successfully created'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update round in storage.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request): JsonResponse
    {
        try {
            $round = Round::find($request->id);

            $round->score = intval($request->score);
            $round->created_at = $request->created_at;
            $round->save();

            // calc new handicap
            $this->update_golfer_handicap($request->golfer_id);

            return response()->json(['success' => 'Round was successfully updated'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id): JsonResponse
    {   
        
        try {
            $round = Round::find($id);
            $golfer_id = $round->golfer_id;
            $round->delete();

            // calc new handicap
            $this->update_golfer_handicap($golfer_id);

            return response()->json(['success' => 'Round was successfully deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
