<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Church>
 */
class ChurchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'icon_url' => '/image/default_icon.webp',
            'logo_url' => '/image/default_logo.jpg',
            'worship_time_image' => '/image/default_worship.png',
            'address' => fake()->address(),
            'address_url' => '/image/default_address.png',
        ];
    }
}
