<?php

declare(strict_types=1);

use App\Actions\Activity\DetectActiveProject;
use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Events\ActivityDetected;
use App\Events\ActivityWithoutSessionDetected;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'activity');

describe('RecordActivityEvent', function () {
    beforeEach(fn () => Event::fake());

    it('returns null when project cannot be resolved', function () {
        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
        );

        $result = RecordActivityEvent::make()->handle($data);

        expect($result)->toBeNull();
        $this->assertDatabaseEmpty('activity_events');
    });

    it('dispatches ActivityWithoutSessionDetected when project found but no active session', function () {
        $project = Project::factory()->create();

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            projectId: $project->id,
        );

        $result = RecordActivityEvent::make()->handle($data);

        expect($result)->toBeNull();
        $this->assertDatabaseEmpty('activity_events');
        Event::assertDispatched(ActivityWithoutSessionDetected::class,
            fn ($e) => $e->project->id === $project->id
        );
    });

    it('dispatches ActivityWithoutSessionDetected when active session belongs to a different project', function () {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        Session::factory()->create(['project_id' => $otherProject->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            projectId: $project->id,
        );

        $result = RecordActivityEvent::make()->handle($data);

        expect($result)->toBeNull();
        Event::assertDispatched(ActivityWithoutSessionDetected::class);
    });

    it('creates an ActivityEvent and links to active session', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            metadata: ['hash' => 'abc123'],
            projectId: $project->id,
        );

        $event = RecordActivityEvent::make()->handle($data);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->project_id)->toBe($project->id)
            ->and($event->session_id)->toBe($session->id)
            ->and($event->type)->toBe(ActivityEventType::GitCommit);

        $this->assertDatabaseHas('activity_events', [
            'project_id' => $project->id,
            'session_id' => $session->id,
            'source_type' => 'git',
        ]);
    });

    it('dispatches ActivityDetected when session is active', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            projectId: $project->id,
        );

        RecordActivityEvent::make()->handle($data);

        Event::assertDispatched(ActivityDetected::class);
        Event::assertNotDispatched(ActivityWithoutSessionDetected::class);
    });

    it('resolves project from filePath when projectId is null', function () {
        $repo = ProjectRepository::factory()->create(['local_path' => '/tmp/test-project']);
        $project = Project::find($repo->project_id);
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::FileChange,
            sourceType: 'fswatch',
            occurredAt: CarbonImmutable::now(),
            filePath: '/tmp/test-project/src/file.php',
        );

        $event = RecordActivityEvent::make()->handle($data);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->project_id)->toBe($repo->project_id)
            ->and($event->project_repository_id)->toBe($repo->id);
    });

    it('skips DetectActiveProject when projectId is already set', function () {
        DetectActiveProject::fake()->shouldNotReceive('handle');

        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            projectId: $project->id,
            filePath: '/tmp/project/file.php',
        );

        RecordActivityEvent::make()->handle($data);
    });
});
