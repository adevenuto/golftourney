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
            'tournament_name' => 'Black League',
        ]);
    }
}
