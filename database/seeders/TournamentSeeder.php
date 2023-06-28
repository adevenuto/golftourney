<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TournamentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tournaments')->insert([
            'entry_cost' => 15,
            'skin_prox_cost' => 15,
            'hole_count' => 9,
            'course_name' => 'Robert A. Black',
            'tournament_name' => 'Black League',
        ]);
    }
}
