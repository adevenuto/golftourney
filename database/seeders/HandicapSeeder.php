<?php

namespace Database\Seeders;

use Log;
use App\Models\Golfer;
use App\Traits\HandicapTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;


class HandicapSeeder extends Seeder
{   
    use HandicapTrait;

    public function run(): void
    { 
        $golfers = Golfer::all();
        foreach ($golfers as $golfer) {
            $rounds = $this->latest_rounds($golfer->id);
            if (count($rounds)>0) {
                $handicap = $this->calc_handicap($rounds);
                $golfer = Golfer::where('id', $golfer->id)->first();
                $golfer->handicap = $handicap;
                $golfer->save();
            }
        }
    }
}

