<?php

declare(strict_types=1);

use App\Models\Project;

pest()->group('project');

describe('list projects', function () {
    it('renders the index page with projects', function () {
        Project::factory()->count(3)->create();

        $this->get(route('projects.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('project/Index')
                ->has('projects.data', 3)
            );
    });

    it('orders projects alphabetically by name', function () {
        $projectC = Project::factory()->create(['name' => 'Charlie']);
        $projectA = Project::factory()->create(['name' => 'Alpha']);
        $projectB = Project::factory()->create(['name' => 'Beta']);

        $this->get(route('projects.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('projects.data.0.id', $projectA->id)
                ->where('projects.data.1.id', $projectB->id)
                ->where('projects.data.2.id', $projectC->id)
            );
    });
})->group('controllers');
