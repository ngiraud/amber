<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Session;

pest()->group('session');

describe('list sessions', function () {
    it('renders the index page with sessions', function () {
        Session::factory()->count(3)->completed()->for(Project::factory()->create())->create();

        $this->get(route('sessions.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Index')
                ->has('sessions.data', 3)
            );
    });

    it('renders the index page with active projects for the start session form', function () {
        $this->get(route('sessions.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Index')
                ->has('projects')
            );
    });
})->group('controllers');
