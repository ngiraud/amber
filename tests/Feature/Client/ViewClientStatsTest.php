<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Project;
use App\Models\Session;

pest()->group('client');

describe('view client stats', function () {
    it('aggregates rounded and real totals across the client projects', function () {
        $client = Client::factory()->create();
        $projectA = Project::factory()->for($client)->create(['daily_reference_hours' => 8]);
        $projectB = Project::factory()->for($client)->create(['daily_reference_hours' => 8]);

        Session::factory()->for($projectA)->create([
            'date' => '2026-06-01',
            'duration_minutes' => 360,
            'rounded_minutes' => 480,
            'ended_at' => now(),
        ]);
        Session::factory()->for($projectB)->create([
            'date' => '2026-06-02',
            'duration_minutes' => 240,
            'rounded_minutes' => 240,
            'ended_at' => now(),
        ]);

        $this->get(route('clients.show', $client))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('client/Show')
                ->where('client_stats.total_minutes', 720)
                ->where('client_stats.total_real_minutes', 600)
                ->where('client_stats.total_days', 1.5)
                ->where('client_stats.total_real_days', 1.25)
            );
    });

    it('converts each project with its own daily reference hours', function () {
        $client = Client::factory()->create();
        $shortDays = Project::factory()->for($client)->create(['daily_reference_hours' => 4]);
        $longDays = Project::factory()->for($client)->create(['daily_reference_hours' => 8]);

        Session::factory()->for($shortDays)->create([
            'date' => '2026-06-01',
            'duration_minutes' => 240,
            'rounded_minutes' => 240,
            'ended_at' => now(),
        ]);
        Session::factory()->for($longDays)->create([
            'date' => '2026-06-02',
            'duration_minutes' => 480,
            'rounded_minutes' => 480,
            'ended_at' => now(),
        ]);

        $this->get(route('clients.show', $client))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                // 240/240 (1 day) + 480/480 (1 day) = 2 days
                ->where('client_stats.total_days', 2)
                ->where('client_stats.total_real_days', 2)
            );
    });
})->group('controllers');
