<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\Golfer;
use Illuminate\Http\Request;
use App\Traits\HandicapTrait;
use Illuminate\Support\Facades\DB;

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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('rounds.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        try {
            $round = Round::find($request->id);
            $golfer = Golfer::where('id', $request->golfer_id)->first();

            $round->score = intval($request->score);
            $round->created_at = $request->created_at;
            $round->save();

            // calc new handicap
            $latest_rounds = $this->latest_rounds($golfer->id);
            $new_handicap = $this->calc_handicap($latest_rounds);

            $golfer->handicap = $new_handicap;
            $golfer->save();

            // return response()->json(['success' => 'Round was successfully updated'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a resource in storage.
     *
     * @return Response
     */
    public function delete($id)
    {   
        
        try {
            $round = Round::find($id);
            $golfer = Golfer::where('id', $round->golfer_id)->first();
            
            $round->delete();

            // calc new handicap
            $latest_rounds = $this->latest_rounds($golfer->id);
            $new_handicap = $this->calc_handicap($latest_rounds);

            $golfer->handicap = $new_handicap;
            $golfer->save();

            return response()->json(['success' => 'Round was successfully deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
