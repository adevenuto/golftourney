<?php

namespace Database\Seeders;

use App\Models\League;
use App\Models\Round;
use App\Models\User;
use App\Services\HandicapService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a coherent multi-league demo: an admin running one league with a
     * roster of golfers and rounds, handicaps computed per the league's course.
     */
    public function run(HandicapService $handicaps): void
    {
        $admin = User::factory()->create([
            'first_name' => 'anthony',
            'last_name' => 'devenuto',
            'email' => 'anthonydevenuto@gmail.com',
        ]);

        $league = League::factory()->create([
            'name' => 'The Black League',
            'owner_id' => $admin->id,
        ]);
        $league->members()->attach($admin->id, ['role' => 'admin', 'handicap' => 0]);
        $admin->update(['current_league_id' => $league->id]);

        User::factory(25)->roster()->create()->each(function (User $user) use ($league, $handicaps) {
            $user->leagues()->attach($league->id, ['role' => 'player', 'handicap' => 0]);
            Round::factory(random_int(5, 25))->for($user)->create(['league_id' => $league->id]);
            $handicaps->recalculateFor($user, $league);
        });
    }
}
