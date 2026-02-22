<?php

declare(strict_types=1);

use App\Events\ActivityWithoutSessionDetected;
use App\Listeners\HandleActivityWithoutSessionDetected;
use App\Models\Project;
use Illuminate\Support\Facades\Event;

pest()->group('listeners', 'activity');

describe('HandleActivityWithoutSessionDetected', function () {
    beforeEach(fn () => Event::fake());

    it('sends a notification with the project name', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create(['name' => 'My Project']);
        $event = new ActivityWithoutSessionDetected($project);

        app(HandleActivityWithoutSessionDetected::class)->handle($event);

        $mock->shouldHaveReceived('title')->once()->with('Activity Detected');
        $mock->shouldHaveReceived('reference')->once()->with($project->id);
        $mock->shouldHaveReceived('addAction')->once()->with('Start Session');
    });

    it('includes the project name in the notification message', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create(['name' => 'Acme Corp']);
        $event = new ActivityWithoutSessionDetected($project);

        app(HandleActivityWithoutSessionDetected::class)->handle($event);

        $mock->shouldHaveReceived('message')->once()->withArgs(
            fn (string $msg) => str_contains($msg, 'Acme Corp')
        );
    });
});

describe('HandleNotificationStartSession', function () {
    it('starts a session when action index 0 is clicked and reference is a valid project', function () {
        Event::fake();

        $project = Project::factory()->create();

        $event = new Native\Desktop\Events\Notifications\NotificationActionClicked(
            reference: $project->id,
            index: 0,
            event: '',
        );

        app(App\Listeners\HandleNotificationStartSession::class)->handle($event);

        $this->assertDatabaseHas('sessions', ['project_id' => $project->id]);
    });

    it('does nothing when action index is not 0', function () {
        Event::fake();

        $project = Project::factory()->create();

        $event = new Native\Desktop\Events\Notifications\NotificationActionClicked(
            reference: $project->id,
            index: 1,
            event: '',
        );

        app(App\Listeners\HandleNotificationStartSession::class)->handle($event);

        $this->assertDatabaseEmpty('sessions');
    });

    it('does nothing when reference does not match a project', function () {
        Event::fake();

        $event = new Native\Desktop\Events\Notifications\NotificationActionClicked(
            reference: 'nonexistent-id',
            index: 0,
            event: '',
        );

        app(App\Listeners\HandleNotificationStartSession::class)->handle($event);

        $this->assertDatabaseEmpty('sessions');
    });
});
