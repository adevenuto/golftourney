<?php

namespace Database\Factories;

use App\Models\League;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Defaults mirror the 9-hole Black League so handicap math stays stable.
        return [
            'name' => fake()->unique()->lastName().' League',
            'owner_id' => User::factory(),
            'holes' => 9,
            'par' => 33,
            'course_rating' => 31.5,
            'slope_rating' => 104,
            'recent_rounds' => 20,
            'counting_rounds' => 8,
            'league_only' => true,
            'display_nine_hole_index' => false,
        ];
    }
}
