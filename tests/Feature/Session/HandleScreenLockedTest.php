<?php

declare(strict_types=1);

use App\Actions\Session\StopSession;
use App\Enums\SessionSource;
use App\Listeners\HandleScreenLocked;
use App\Models\Project;
use App\Models\Session;
use Native\Desktop\Events\PowerMonitor\ScreenLocked;

pest()->group('listeners', 'session');

describe('HandleScreenLocked', function () {
    it('stops an active manual session on screen lock', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id, 'source' => SessionSource::Manual]);

        StopSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id));

        $listener = app(HandleScreenLocked::class);
        $listener->handle(new ScreenLocked);
    });

    it('does nothing when no session is active', function () {
        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleScreenLocked::class);
        $listener->handle(new ScreenLocked);
    });

    it('does nothing when the active session is auto', function () {
        $project = Project::factory()->create();
        Session::factory()->auto()->create(['project_id' => $project->id]);

        StopSession::fake()->shouldNotReceive('handle');

        $listener = app(HandleScreenLocked::class);
        $listener->handle(new ScreenLocked);
    });
});
