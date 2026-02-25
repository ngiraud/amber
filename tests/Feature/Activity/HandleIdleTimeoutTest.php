<?php

declare(strict_types=1);

use App\Actions\Session\StopSession;
use App\Events\IdleTimeoutReached;
use App\Listeners\HandleIdleTimeout;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('listeners', 'activity');

describe('HandleIdleTimeout', function () {
    beforeEach(fn () => Event::fake());

    it('stops the session via StopSession action', function () {
        fakeNotification();
        $project = Project::factory()->create();
        $session = Session::factory()->create([
            'project_id' => $project->id,
            'ended_at' => null,
            'started_at' => now()->subHour(),
        ]);

        $event = new IdleTimeoutReached($session, CarbonImmutable::now()->subMinutes(20));

        app(HandleIdleTimeout::class)->handle($event);

        $session->refresh();
        expect($session->ended_at)->not->toBeNull();
    });

    it('delegates to StopSession action', function () {
        fakeNotification();
        $project = Project::factory()->create();
        $session = Session::factory()->create([
            'project_id' => $project->id,
            'ended_at' => null,
        ]);

        StopSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id))
            ->andReturn($session->fresh());

        $event = new IdleTimeoutReached($session, CarbonImmutable::now()->subMinutes(20));

        app(HandleIdleTimeout::class)->handle($event);
    });

    it('sends an auto-stopped notification', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create(['name' => 'My Project']);
        $session = Session::factory()->create([
            'project_id' => $project->id,
            'ended_at' => null,
            'started_at' => now()->subHour(),
        ]);

        $event = new IdleTimeoutReached($session, CarbonImmutable::now()->subMinutes(20));

        app(HandleIdleTimeout::class)->handle($event);

        $mock->shouldHaveReceived('title')->once()->with('Session Auto-Stopped');
    });
});
