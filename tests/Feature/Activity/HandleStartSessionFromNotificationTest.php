<?php

declare(strict_types=1);

use App\Actions\Session\StartSession;
use App\Listeners\HandleStartSessionFromNotification;
use App\Models\Project;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Events\Notifications\NotificationActionClicked;

pest()->group('listeners', 'activity');

describe('HandleStartSessionFromNotification', function () {
    beforeEach(fn () => Event::fake());

    it('starts a session for the project in the reference when index is 0', function () {
        $project = Project::factory()->create();

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id));

        $event = new NotificationActionClicked($project->id, 0, '');

        app(HandleStartSessionFromNotification::class)->handle($event);
    });

    it('does nothing when the action index is not 0', function () {
        $project = Project::factory()->create();

        StartSession::fake()
            ->shouldReceive('handle')
            ->never();

        $event = new NotificationActionClicked($project->id, 1, '');

        app(HandleStartSessionFromNotification::class)->handle($event);
    });

    it('does nothing when the reference does not match a project', function () {
        StartSession::fake()
            ->shouldReceive('handle')
            ->never();

        $event = new NotificationActionClicked('non-existent-id', 0, '');

        app(HandleStartSessionFromNotification::class)->handle($event);
    });
});
