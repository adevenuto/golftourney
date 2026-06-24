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
        return [
            'user_id' => User::factory(),
            'league_id' => League::factory(),
            'score' => fake()->numberBetween(35, 60),
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
