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
        ->leftJoin('rounds', 'golfers.id', '=', 'rounds.golfer_id')
        ->orderBy('rounds.created_at', 'desc')
        ->where('golfers.id', $id)
        ->limit('20')
        ->get()
        ->toArray();

        return collect($latest20)->sortBy('score')->take(8)->toArray();
    }


    /**
     * @param array $rounds
     *
     * @return float
     */
    public function calc_handicap(Array $rounds): float
    {   
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
}