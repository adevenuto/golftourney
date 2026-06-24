<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Round>
 */
class RoundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Self-contained course context, matching the 9-hole Black League the
        // LeagueFactory defaults to (callers override via WithLeague::roundFor).
        return [
            'user_id' => User::factory(),
            'league_id' => League::factory(),
            'course_rating' => 31.5,
            'slope_rating' => 104,
            'par' => 33,
            'holes' => 9,
            'score' => fake()->numberBetween(35, 60),
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
