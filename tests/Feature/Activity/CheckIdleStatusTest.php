<?php

declare(strict_types=1);

use App\Actions\Activity\CheckIdleStatus;
use App\Events\IdleTimeoutReached;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'activity');

describe('CheckIdleStatus', function () {
    beforeEach(function () {
        Event::fake();
        config()->set('activity.idle_timeout_minutes', 15);
    });

    it('does nothing when there is no active session', function () {
        CheckIdleStatus::make()->handle();

        Event::assertNotDispatched(IdleTimeoutReached::class);
    });

    it('does not dispatch when there is recent activity', function () {
        $projectRepository = ProjectRepository::factory()->create(['local_path' => '/tmp/project']);

        Session::factory()->create(['project_id' => $projectRepository->project_id, 'ended_at' => null]);
        ActivityEvent::factory()->create([
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'occurred_at' => now()->subMinutes(5),
        ]);

        CheckIdleStatus::make()->handle();

        Event::assertNotDispatched(IdleTimeoutReached::class);
    });

    it('dispatches IdleTimeoutReached when idle exceeds the threshold', function () {
        $projectRepository = ProjectRepository::factory()->create(['local_path' => '/tmp/project']);
        Session::factory()->create(['project_id' => $projectRepository->project_id, 'ended_at' => null]);
        ActivityEvent::factory()->create([
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'occurred_at' => now()->subMinutes(20),
        ]);

        CheckIdleStatus::make()->handle();

        Event::assertDispatched(IdleTimeoutReached::class);
    });

    it('falls back to session started_at when there are no activity events', function () {
        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'ended_at' => null,
            'started_at' => now()->subMinutes(20),
        ]);

        CheckIdleStatus::make()->handle();

        Event::assertDispatched(IdleTimeoutReached::class);
    });

    it('does not dispatch when session was just started and no events exist', function () {
        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'ended_at' => null,
            'started_at' => now()->subMinutes(5),
        ]);

        CheckIdleStatus::make()->handle();

        Event::assertNotDispatched(IdleTimeoutReached::class);
    });
});
