<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id' => \App\Models\Church::factory(),
            'name' => fake()->words(2, true),
            'icon_image' => '/image/default_department.png',
        ];
    }

    /**
     * Indicate that the department has no church.
     */
    public function withoutChurch(): static
    {
        return $this->state(fn (array $attributes) => [
            'church_id' => null,
        ]);
    }
}
