<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;

pest()->group('session');

describe('show session', function () {
    it('renders the show page with session and events', function () {
        $session = Session::factory()->completed()->for(Project::factory()->create())->create();
        ActivityEvent::factory()->count(3)->for($session)->for($session->project)->create();

        $this->get(route('sessions.show', $session))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Show')
                ->where('session.id', $session->id)
                ->has('events.data', 3)
                ->where('hasNewEvents', false)
            );
    });

    it('renders the show page with empty events when none exist', function () {
        $session = Session::factory()->completed()->for(Project::factory()->create())->create();

        $this->get(route('sessions.show', $session))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('session/Show')
                ->where('session.id', $session->id)
                ->has('events.data', 0)
            );
    });
})->group('controllers');
