<?php

namespace Database\Factories;

use App\Models\Golfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Golfer>
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
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(###) ###-####'),
        ];
    }
}
