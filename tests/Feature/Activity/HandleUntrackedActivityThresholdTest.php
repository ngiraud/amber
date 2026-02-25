<?php

declare(strict_types=1);

use App\Events\UntrackedActivityThresholdReached;
use App\Listeners\HandleUntrackedActivityThreshold;
use App\Models\Project;
use Illuminate\Support\Facades\Event;

pest()->group('listeners', 'activity');

describe('HandleUntrackedActivityThreshold', function () {
    beforeEach(fn () => Event::fake());

    it('sends an activity detected notification', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create(['name' => 'My Project']);

        $event = new UntrackedActivityThresholdReached($project, 5);

        app(HandleUntrackedActivityThreshold::class)->handle($event);

        $mock->shouldHaveReceived('title')->once()->with('Activity Detected');
    });

    it('includes the project name in the notification message', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create(['name' => 'My Project']);

        $event = new UntrackedActivityThresholdReached($project, 5);

        app(HandleUntrackedActivityThreshold::class)->handle($event);

        $mock->shouldHaveReceived('message')->once()->with(Mockery::on(
            fn (string $msg) => str_contains($msg, 'My Project'),
        ));
    });

    it('sets the project id as reference and adds a start session action', function () {
        $mock = fakeNotification();
        $project = Project::factory()->create();

        $event = new UntrackedActivityThresholdReached($project, 5);

        app(HandleUntrackedActivityThreshold::class)->handle($event);

        $mock->shouldHaveReceived('reference')->once()->with($project->id);
        $mock->shouldHaveReceived('addAction')->once()->with('Start Session');
    });
});
