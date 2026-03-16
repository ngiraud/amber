<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;

pest()->group('controllers', 'dashboard');

test('home renders the dashboard page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('date')
            ->has('sessions')
            ->has('session_stats')
            ->has('week_minutes')
            ->has('month_minutes')
        );
});

describe('session stats', function () {
    it('reflects only finished sessions for today', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 90,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 30,
            'ended_at' => null, // active session — excluded from stats
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('session_stats.total_minutes', 90)
                ->where('session_stats.session_count', 1)
            );
    });

    it('excludes sessions from other days', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today->subDay(),
            'rounded_minutes' => 120,
            'ended_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('session_stats.total_minutes', 60)
                ->where('session_stats.session_count', 1)
            );
    });
});

describe('week and month minutes', function () {
    it('sums week_minutes for all finished sessions this week', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today->startOfWeek(),
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 90,
            'ended_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('week_minutes', 150)
            );
    });

    it('sums month_minutes for all finished sessions this month', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today->startOfMonth(),
            'rounded_minutes' => 120,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 30,
            'ended_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('month_minutes', 150)
            );
    });
});
