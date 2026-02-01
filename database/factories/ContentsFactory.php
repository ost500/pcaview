<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contents>
 */
class ContentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'church_id'      => Church::factory(),
            'department_id'  => Department::factory(),
            'user_id'        => User::factory(),
            'type'           => $this->faker->randomElement(['bulletin', 'news', 'video', 'sermon']),
            'title'          => $this->faker->sentence(),
            'body'           => $this->faker->paragraphs(3, true),
            'file_url'       => null,
            'thumbnail_url'  => null,
            'published_at'   => $this->faker->dateTimeBetween('-1 month', 'now'),
            'is_hide'        => false,
        ];
    }

    /**
     * Indicate that the content is news type.
     */
    public function news(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'news',
        ]);
    }

    /**
     * Indicate that the content is bulletin type.
     */
    public function bulletin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bulletin',
        ]);
    }

    /**
     * Indicate that the content is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hide' => true,
        ]);
    }
}
