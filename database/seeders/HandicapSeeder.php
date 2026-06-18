<?php

namespace Database\Seeders;

use App\Models\Golfer;
use App\Services\HandicapService;
use Illuminate\Database\Seeder;

class HandicapSeeder extends Seeder
{
    public function run(HandicapService $handicaps): void
    {
        Golfer::all()->each(function (Golfer $golfer) use ($handicaps) {
            if ($golfer->rounds()->exists()) {
                $handicaps->recalculateFor($golfer);
            }
        });
    }
}
