<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TournamentConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tournament_config')->insert([
            'entry_cost' => 15,
            'skin_prox_cost' => 15,
            'hole_count' => 9,
            'course_name' => 'Robert A. Black',
            'course_details' => '{"holes": [{"hole-1": {"par": "5", "length": "473"}}, {"hole-2": {"par": "3", "length": "133"}}, {"hole-3": {"par": "4", "length": "341"}}, {"hole-4": {"par": "3", "length": "158"}}, {"hole-5": {"par": "4", "length": "309"}}, {"hole-6": {"par": "4", "length": "313"}}, {"hole-7": {"par": "3", "length": "161"}}, {"hole-8": {"par": "3", "length": "105"}}, {"hole-9": {"par": "4", "length": "346"}}]}',
            'tournament_name' => 'Black League',
        ]);
    }
}
