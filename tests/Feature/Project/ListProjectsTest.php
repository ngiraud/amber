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
})->group('controllers');
