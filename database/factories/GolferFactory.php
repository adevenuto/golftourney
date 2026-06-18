<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Golfer>
 */
class GolferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => strtolower(fake()->firstName()),
            'last_name' => strtolower(fake()->lastName()),
            'handicap' => 0,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
        ];
    }
}
