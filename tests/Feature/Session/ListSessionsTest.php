<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
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
                ->where('selectedSession', null)
                ->has('events.data', 0)
                ->where('hasNewEvents', false)
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

    it('loads the selected session and its events when session route param is provided', function () {
        $session = Session::factory()->completed()->for(Project::factory()->create())->create();
        ActivityEvent::factory()->count(2)->for($session)->for($session->project)->create();

        $this->get(route('sessions.index', ['session' => $session->id]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Index')
                ->where('selectedSession.id', $session->id)
                ->has('events.data', 2)
                ->where('hasNewEvents', false)
            );
    });
})->group('controllers');

