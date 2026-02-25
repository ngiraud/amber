<?php

declare(strict_types=1);

use App\Actions\Activity\CheckUntrackedActivity;
use App\Events\UntrackedActivityThresholdReached;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'activity');

describe('CheckUntrackedActivity', function () {
    beforeEach(function () {
        Event::fake();
        Cache::flush();
        config()->set('activity.untracked_threshold_minutes', 15);
    });

    it('does nothing when there is an active session', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        CheckUntrackedActivity::make()->handle();

        Event::assertNotDispatched(UntrackedActivityThresholdReached::class);
    });

    it('does nothing when there are no untracked events', function () {
        CheckUntrackedActivity::make()->handle();

        Event::assertNotDispatched(UntrackedActivityThresholdReached::class);
    });

    it('does not dispatch when untracked activity is too recent', function () {
        $projectRepository = ProjectRepository::factory()->create();
        ActivityEvent::factory()->create([
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'session_id' => null,
            'occurred_at' => now()->subMinutes(5),
        ]);

        CheckUntrackedActivity::make()->handle();

        Event::assertNotDispatched(UntrackedActivityThresholdReached::class);
    });

    it('dispatches when untracked activity exceeds the threshold', function () {
        $projectRepository = ProjectRepository::factory()->create();
        ActivityEvent::factory()->create([
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'session_id' => null,
            'occurred_at' => now()->subMinutes(20),
        ]);

        CheckUntrackedActivity::make()->handle();

        Event::assertDispatched(UntrackedActivityThresholdReached::class);
    });

    it('picks the project with the most untracked events', function () {
        $repoA = ProjectRepository::factory()->create();
        $repoB = ProjectRepository::factory()->create();

        ActivityEvent::factory()->count(3)->create([
            'project_id' => $repoA->project_id,
            'project_repository_id' => $repoA->id,
            'session_id' => null,
            'occurred_at' => now()->subMinutes(20),
        ]);

        ActivityEvent::factory()->create([
            'project_id' => $repoB->project_id,
            'project_repository_id' => $repoB->id,
            'session_id' => null,
            'occurred_at' => now()->subMinutes(20),
        ]);

        CheckUntrackedActivity::make()->handle();

        Event::assertDispatched(
            UntrackedActivityThresholdReached::class,
            fn ($e) => $e->project->id === $repoA->project_id,
        );
    });

    it('does not dispatch again when cache lock is active', function () {
        $projectRepository = ProjectRepository::factory()->create();
        ActivityEvent::factory()->create([
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'session_id' => null,
            'occurred_at' => now()->subMinutes(20),
        ]);

        CheckUntrackedActivity::make()->handle();
        CheckUntrackedActivity::make()->handle();

        Event::assertDispatched(UntrackedActivityThresholdReached::class, 1);
    });
});
