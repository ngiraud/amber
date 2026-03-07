<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityReport;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityReportLine>
 */
class ActivityReportLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minutes = fake()->numberBetween(30, 480);

        return [
            'activity_report_id' => ActivityReport::factory(),
            'project_id' => Project::factory(),
            'date' => fake()->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'minutes' => $minutes,
            'days' => round($minutes / 60 / 8, 2),
            'description' => fake()->sentence(),
        ];
    }
}
