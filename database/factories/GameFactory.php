<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 9-hole context, matching the LeagueFactory defaults. join_code is
        // assigned by the model's creating hook.
        return [
            'owner_id' => User::factory(),
            'course_id' => null,
            'teebox' => null,
            'holes' => 9,
            'par' => 33,
            'course_rating' => 31.5,
            'slope_rating' => 104,
            'status' => Game::STATUS_LOBBY,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => Game::STATUS_ACTIVE, 'started_at' => now()]);
    }
}
