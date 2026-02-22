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

describe('show session', function () {
    it('renders the show page with session data', function () {
        $session = Session::factory()->completed()->for(Project::factory()->create())->create();

        $this->get(route('sessions.show', $session))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Show')
                ->has('session')
                ->where('session.id', $session->id)
            );
    });
})->group('controllers');
