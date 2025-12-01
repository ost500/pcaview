<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trend>
 */
class TrendFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'link' => 'https://trends.google.com/trending/rss?geo=KR',
            'image_url' => fake()->imageUrl(),
            'traffic_count' => fake()->numberBetween(50, 200),
            'pub_date' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
