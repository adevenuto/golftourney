<?php

namespace App\Traits;
use App\Models\Golfer;
use Illuminate\Support\Facades\DB;

trait HandicapTrait 
{

    /**
     * @param int $id
     *
     * @return array
     */
    public function latest_rounds(Int $id): array
    {
        $latest20 = DB::table('golfers')
        ->join('rounds', 'golfers.id', '=', 'rounds.golfer_id')
        ->orderBy('rounds.created_at', 'desc')
        ->where('golfers.id', $id)
        ->limit('20')
        ->get();

        return collect($latest20)->sortBy('score')->take(8)->values()->toArray();
    }


    /**
     * @param array $rounds
     *
     * @return float
     */
    public function calc_handicap(Array $rounds): float
    {   
        if(count($rounds)===0) return 0.00;
        $score_diff_sum = 0;
        foreach ($rounds as $round) {
            if($round->score) {
                $score = $round->score;
                $score_diff = ($score-31.5)*113/104;
                $score_diff_sum += $score_diff;
            }
        }
        $handicap = round($score_diff_sum/count($rounds),3);
        return $handicap;
    }


    /**
     * @param int $id
     *
     */
    public function update_golfer_handicap(Int $id)
    {   
        $golfer = Golfer::where('id', $id)->first();

        // calc new handicap
        $latest_rounds = $this->latest_rounds($id);
        $new_handicap = $this->calc_handicap($latest_rounds);

        $golfer->handicap = $new_handicap;
        $golfer->save();
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function total_rounds(Int $id): int
    {
        $count = DB::table('rounds')
        ->where('golfer_id', $id)
        ->count();
        return $count ?? 0;
    }
}