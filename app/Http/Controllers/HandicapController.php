<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\Golfer;
use App\Traits\HandicapTrait;
use Illuminate\Http\Request;

class HandicapController extends Controller
{
    use HandicapTrait;

    /**
     * Store new round, return .
     * @param int $id
     * @param int $newscore
     * 
     * @return Response
     */
    public function store(Int $id, Int $newScore)
    {   
        try {
            // store new round
            $golfer = Golfer::find($id);
            $round = new Round();
            $round->golfer_id = $golfer->id;
            $round->score = number_format($newScore,2);
            $round->course_name = 'Robert A. Black';
            $round->save();

            // calc new handicap
            $latest_rounds = $this->latest_rounds($golfer->id);
            $new_handicap = $this->calc_handicap($latest_rounds);

            $golfer->handicap = $new_handicap;
            $golfer->save();

            return response()->json(['success' => 'score updated'], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Return golfers latest rounds
     * @param int $id
     * 
     * @return Response
     */
    public function latest(Int $id)
    {   
        try {
            $golfer = Golfer::find($id);
            $latest_rounds = $this->latest_rounds($golfer->id);
            return response()->json(['latest_rounds' => $latest_rounds], 200);
        } catch (\exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
