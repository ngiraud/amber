<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;

pest()->group('controllers', 'activity');

describe('ActivityEventController', function () {
    it('renders the activity index page', function () {
        $this->get(route('activity.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('activity/Index'));
    });

    it('includes hasEnabledSources in the response', function () {
        $this->get(route('activity.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('hasEnabledSources'));
    });

    it('includes hasNewEvents when since_occurred_at is a valid timestamp', function () {
        $this->get(route('activity.index', ['since_occurred_at' => now()->subMinute()->timestamp]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('hasNewEvents'));
    });

    it('hasNewEvents is true when new events exist after the given timestamp', function () {
        $project = Project::factory()->create();
        $since = now()->subMinutes(5)->timestamp;

        ActivityEvent::factory()->for($project)->create(['occurred_at' => now()->subMinute()]);

        $this->get(route('activity.index', ['since_occurred_at' => $since]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hasNewEvents', true));
    });

    it('hasNewEvents is false when no events exist after the given timestamp', function () {
        $project = Project::factory()->create();
        $since = now()->timestamp;

        ActivityEvent::factory()->for($project)->create(['occurred_at' => now()->subHour()]);

        $this->get(route('activity.index', ['since_occurred_at' => $since]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('hasNewEvents', false));
    });
});

describe('EventsViewModel on projects.show', function () {
    it('renders the project show page with events', function () {
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('project/Show')
                ->has('events')
            );
    });
});

describe('EventsViewModel on clients.show', function () {
    it('renders the client show page with events', function () {
        $client = Client::factory()->create();

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('client/Show')
                ->has('events')
            );
    });
});
