<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoundingStrategy;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->words(2, true),
            'color' => fake()->hexColor(),
            'is_active' => true,
            'daily_reference_hours' => 8,
            'rounding' => RoundingStrategy::Quarter,
            'hourly_rate' => fake()->optional(0.7)->randomFloat(2, 50, 200),
            'daily_rate' => fake()->optional(0.7)->randomFloat(2, 300, 1500),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
