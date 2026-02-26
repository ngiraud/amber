<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityEventType;
use App\Models\Project;
use App\Models\ProjectRepository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityEvent>
 */
class ActivityEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'project_repository_id' => ProjectRepository::factory(),
            'session_id' => null,
            'source_type' => 'git',
            'type' => ActivityEventType::GitCommit,
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'metadata' => [],
        ];
    }
}
