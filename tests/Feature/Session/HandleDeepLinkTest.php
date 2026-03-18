<?php

declare(strict_types=1);

use App\Actions\Activity\ScanActivitySources;
use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Actions\Session\SwitchSessionProject;
use App\Data\ScanActivityResult;
use App\Listeners\HandleDeepLink;
use App\Models\Project;
use App\Models\Session;
use Native\Desktop\Contracts\WindowManager;
use Native\Desktop\Events\App\OpenedFromURL;
use Native\Desktop\Facades\Window;
use Native\Desktop\Windows\Window as WindowInstance;

pest()->group('deeplink', 'session', 'listeners');

describe('session/start', function () {
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
});

describe('session/stop', function () {
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
});

describe('session/toggle', function () {
    it('starts a session when no session is active', function () {
        $project = Project::factory()->create();

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id));

        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL("amber://session/toggle?project={$project->id}"));
    });

    it('stops the active session when one is already running', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id]);

        StopSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id));

        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL("amber://session/toggle?project={$project->id}"));
    });
});

describe('session/switch', function () {
    it('switches the active session to the given project', function () {
        $project = Project::factory()->create();
        $newProject = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id]);

        SwitchSessionProject::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($s) => $s->id === $session->id),
                Mockery::on(fn ($p) => $p->id === $newProject->id),
            );

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL("amber://session/switch?project={$newProject->id}"));
    });

    it('starts a session when no session is active', function () {
        $project = Project::factory()->create();

        SwitchSessionProject::fake()->shouldNotReceive('handle');

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id));

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL("amber://session/switch?project={$project->id}"));
    });

    it('does nothing when no project param is given', function () {
        SwitchSessionProject::fake()->shouldNotReceive('handle');
        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/switch'));
    });

    it('does nothing when the given project does not exist', function () {
        SwitchSessionProject::fake()->shouldNotReceive('handle');
        StartSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://session/switch?project=nonexistent'));
    });
});

describe('navigate', function () {
    beforeEach(function () {
        $windowSpy = Mockery::mock(WindowManager::class);
        $windowInstance = Mockery::mock(WindowInstance::class);
        $windowInstance->shouldReceive('url')->once()->andReturnSelf();
        $windowSpy->shouldReceive('get')->with('main')->andReturn($windowInstance);
        Window::swap($windowSpy);
    });

    it('navigates to the dashboard', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/dashboard'));
    });

    it('navigates to the timeline', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/timeline'));
    });

    it('navigates to reports', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/reports'));
    });

    it('navigates to clients', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/clients'));
    });

    it('navigates to projects', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/projects'));
    });

    it('navigates to sessions', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/sessions'));
    });

    it('navigates to activity', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/activity'));
    });

    it('navigates to settings', function () {
        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/settings'));
    });

});

describe('navigate/unknown', function () {
    it('does nothing for an unknown page', function () {
        $windowSpy = Mockery::mock(WindowManager::class);
        $windowSpy->shouldNotReceive('get');
        Window::swap($windowSpy);

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://navigate/unknown'));
    });
});

describe('activity/sync', function () {
    it('shows a starting notification then scans and shows a completion notification with the event count', function () {
        $events = App\Models\ActivityEvent::factory()->count(3)->create();

        ScanActivitySources::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new ScanActivityResult(events: $events, errors: collect()));

        $notificationMock = Mockery::mock(Native\Desktop\Notification::class);
        $notificationMock->shouldReceive('title')->with('Activity Sync')->once()->andReturnSelf();
        $notificationMock->shouldReceive('message')->with('Scanning activity sources…')->once()->andReturnSelf();
        $notificationMock->shouldReceive('title')->with('Activity Sync Complete')->once()->andReturnSelf();
        $notificationMock->shouldReceive('message')->with('Recorded 3 new activity event(s).')->once()->andReturnSelf();
        $notificationMock->shouldReceive('show')->twice()->andReturnSelf();
        app()->instance(Native\Desktop\Notification::class, $notificationMock);

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://activity/sync'));
    });

    it('shows a no-new-events notification when nothing was found', function () {
        ScanActivitySources::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new ScanActivityResult(events: collect(), errors: collect()));

        $notificationMock = Mockery::mock(Native\Desktop\Notification::class);
        $notificationMock->shouldReceive('title')->andReturnSelf();
        $notificationMock->shouldReceive('message')->with('No new activity events found.')->once()->andReturnSelf();
        $notificationMock->shouldReceive('message')->with('Scanning activity sources…')->once()->andReturnSelf();
        $notificationMock->shouldReceive('show')->twice()->andReturnSelf();
        app()->instance(Native\Desktop\Notification::class, $notificationMock);

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://activity/sync'));
    });
});

describe('unknown paths', function () {
    it('does nothing for unknown deeplink paths', function () {
        StartSession::fake()->shouldNotReceive('handle');
        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleDeepLink::class);
        $listener->handle(new OpenedFromURL('amber://unknown/path'));
    });
});
