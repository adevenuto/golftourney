<?php

namespace Database\Factories;

use App\Models\Golfer;
use App\Models\Round;
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
            'golfer_id' => Golfer::factory(),
            'score' => fake()->numberBetween(35, 60),
            'course_name' => 'Robert A. Black',
            'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
