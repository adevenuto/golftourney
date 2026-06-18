<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => fake()->unique()->numberBetween(1, 1_000_000),
            'api_course_id' => fake()->numberBetween(1, 100_000),
            'course_name' => fake()->company().' Golf Course',
            'club_name' => fake()->company(),
            'street' => fake()->streetAddress(),
            'state' => fake()->randomElement(['IL', 'CA', 'NY', 'TX', 'FL']),
            'postal_code' => fake()->postcode(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'layout_data' => [
                'teeboxes' => [
                    ['name' => 'White', 'slope' => 113, 'courseRating' => 70.0],
                ],
                'hole_count' => 18,
            ],
        ];
    }
}
