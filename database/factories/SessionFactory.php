<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SessionSource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Session>
 */
class SessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'project_id' => Project::factory(),
            'started_at' => $startedAt,
            'ended_at' => null,
            'duration_minutes' => null,
            'source' => SessionSource::Manual,
            'notes' => null,
            'is_validated' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(function () {
            $startedAt = fake()->dateTimeBetween('-30 days', '-1 hour');
            $durationMinutes = fake()->numberBetween(30, 480);
            $endedAt = (clone $startedAt)->modify("+{$durationMinutes} minutes");

            return [
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_minutes' => $durationMinutes,
            ];
        });
    }

    public function validated(): static
    {
        return $this->completed()->state(fn () => ['is_validated' => true]);
    }
}
