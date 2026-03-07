<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityReportStatus;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityReport>
 */
class ActivityReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2026),
            'status' => ActivityReportStatus::Draft,
            'total_minutes' => 0,
            'total_days' => 0,
            'total_amount_ht' => null,
            'generated_at' => null,
            'pdf_path' => null,
            'csv_path' => null,
            'notes' => null,
        ];
    }

    public function generating(): static
    {
        return $this->state(fn () => ['status' => ActivityReportStatus::Generating]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ActivityReportStatus::Draft]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => [
            'status' => ActivityReportStatus::Finalized,
            'generated_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => ActivityReportStatus::Failed]);
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => ActivityReportStatus::Sent,
            'generated_at' => now(),
        ]);
    }
}
