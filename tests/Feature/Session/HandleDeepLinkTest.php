<?php

declare(strict_types=1);

use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Listeners\HandleDeepLink;
use App\Models\Project;
use App\Models\Session;
use Native\Desktop\Events\App\OpenedFromURL;

pest()->group('deeplink', 'session', 'listeners');

describe('HandleDeepLink listener', function () {
    it('starts a session on the given project when opening amber://session/start?project=<id>', function () {
        $project = Project::factory()->create();

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id));

        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL("amber://session/start?project={$project->id}"));
    });

    it('falls back to the first active project when no project query param is given', function () {
        $project = Project::factory()->create(['is_active' => true]);

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id));

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/start'));
    });

    it('does nothing when no active project exists and no project param is given', function () {
        Project::factory()->create(['is_active' => false]);

        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/start'));
    });

    it('does nothing when the given project does not exist', function () {
        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/start?project=nonexistent'));
    });

    it('silently ignores when a session is already active on start', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id]);

        $listener = app(HandleDeepLink::class);

        expect(fn () => $listener->handle(new OpenedFromURL("amber://session/start?project={$project->id}")))
            ->not->toThrow(Throwable::class);
    });

    it('stops the active session when opening amber://session/stop', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id]);

        StopSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id));

        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/stop'));
    });

    it('does nothing when stopping and no session is active', function () {
        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/stop'));
    });

    it('does nothing for unknown deeplink paths', function () {
        StartSession::fake()->shouldNotReceive('handle');
        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://unknown/path'));
    });
});
