<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TimeEntrySource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', '-1 hour');
        $rawMinutes = fake()->numberBetween(15, 480);
        $endedAt = (clone $startedAt)->modify("+{$rawMinutes} minutes");

        return [
            'session_id' => null,
            'project_id' => Project::factory(),
            'date' => $startedAt->format('Y-m-d'),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'raw_minutes' => $rawMinutes,
            'rounded_minutes' => $rawMinutes,
            'source' => TimeEntrySource::Session,
            'description' => null,
            'is_validated' => true,
        ];
    }
}
