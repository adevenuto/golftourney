<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameScore>
 */
class GameScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'hole' => fake()->numberBetween(1, 9),
            'strokes' => fake()->numberBetween(3, 7),
            'putts' => fake()->numberBetween(1, 3),
        ];
    }
}
