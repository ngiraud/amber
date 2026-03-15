<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Session;

pest()->group('project');

test('projects are ordered by most recent session first', function () {
    $projectNoSession = Project::factory()->create(['name' => 'No Sessions']);
    $projectOldSession = Project::factory()->create(['name' => 'Old Session']);
    $projectRecentSession = Project::factory()->create(['name' => 'Recent Session']);

    Session::factory()->create([
        'project_id' => $projectOldSession->id,
        'started_at' => now()->subDays(7),
        'ended_at' => now()->subDays(7)->addHours(2),
    ]);

    Session::factory()->create([
        'project_id' => $projectRecentSession->id,
        'started_at' => now()->subDay(),
        'ended_at' => now()->subDay()->addHours(2),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->reloadOnly('projects', fn ($page) => $page
                ->where('projects.0.id', $projectRecentSession->id)
                ->where('projects.1.id', $projectOldSession->id)
                ->where('projects.2.id', $projectNoSession->id)
            )
        );
});

test('projects with no sessions are ordered alphabetically', function () {
    $projectB = Project::factory()->create(['name' => 'Beta']);
    $projectA = Project::factory()->create(['name' => 'Alpha']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->reloadOnly('projects', fn ($page) => $page
                ->where('projects.0.id', $projectA->id)
                ->where('projects.1.id', $projectB->id)
            )
        );
});
