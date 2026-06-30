<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Session;

pest()->group('project');

describe('view project stats', function () {
    it('exposes rounded and real total minutes separately', function () {
        $project = Project::factory()->create();

        Session::factory()->for($project)->create([
            'date' => '2026-06-01',
            'duration_minutes' => 50,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->for($project)->create([
            'date' => '2026-06-02',
            'duration_minutes' => 20,
            'rounded_minutes' => 30,
            'ended_at' => now(),
        ]);

        $this->get(route('projects.show', $project))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('project/Show')
                ->where('project_stats.total_minutes', 90)
                ->where('project_stats.total_real_minutes', 70)
                ->where('project_stats.worked_days', 2)
            );
    });

    it('converts total minutes to days using the daily reference hours', function () {
        $project = Project::factory()->create(['daily_reference_hours' => 8]);

        Session::factory()->for($project)->create([
            'date' => '2026-06-01',
            'duration_minutes' => 360,
            'rounded_minutes' => 480,
            'ended_at' => now(),
        ]);

        $this->get(route('projects.show', $project))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('project_stats.total_days', 1)
                ->where('project_stats.total_real_days', 0.75)
            );
    });

    it('ignores sessions without an end date', function () {
        $project = Project::factory()->create();

        Session::factory()->for($project)->create([
            'date' => '2026-06-01',
            'duration_minutes' => 50,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->for($project)->create([
            'date' => '2026-06-02',
            'duration_minutes' => null,
            'rounded_minutes' => null,
            'ended_at' => null,
        ]);

        $this->get(route('projects.show', $project))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('project_stats.total_minutes', 60)
                ->where('project_stats.total_real_minutes', 50)
            );
    });
})->group('controllers');
