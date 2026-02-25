<?php

declare(strict_types=1);

use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Events\ActivityDetected;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'activity');

describe('RecordActivityEvent', function () {
    beforeEach(fn () => Event::fake());

    it('creates ActivityEvent with null session_id when no active session', function () {
        $projectRepository = ProjectRepository::factory()->create();

        $data = new ActivityEventData(
            sourceType: ActivityEventSourceType::Git,
            type: ActivityEventType::GitCommit,
            occurredAt: CarbonImmutable::now(),
            projectRepository: $projectRepository,
        );

        $event = RecordActivityEvent::make()->handle($data);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->project_id)->toBe($projectRepository->project_id)
            ->and($event->project_repository_id)->toBe($projectRepository->id)
            ->and($event->session_id)->toBeNull();

        $this->assertDatabaseHas('activity_events', [
            'project_id' => $projectRepository->project_id,
            'project_repository_id' => $projectRepository->id,
            'session_id' => null,
        ]);
    });

    it('creates ActivityEvent with null session_id when active session is for a different project', function () {
        $otherProject = Project::factory()->create();
        $activeSession = Session::factory()->create(['project_id' => $otherProject->id, 'ended_at' => null]);

        $projectRepository = ProjectRepository::factory()->create();

        $data = new ActivityEventData(
            sourceType: ActivityEventSourceType::Git,
            type: ActivityEventType::GitCommit,
            occurredAt: CarbonImmutable::now(),
            projectRepository: $projectRepository,
        );

        $event = RecordActivityEvent::make()->handle($data, $activeSession);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->project_id)->toBe($projectRepository->project_id)
            ->and($event->project_repository_id)->toBe($projectRepository->id)
            ->and($event->session_id)->toBeNull();
    });

    it('creates an ActivityEvent and links to active session', function () {
        $projectRepository = ProjectRepository::factory()->create();
        $activeSession = Session::factory()->create(['project_id' => $projectRepository->project_id, 'ended_at' => null]);

        $data = new ActivityEventData(
            sourceType: ActivityEventSourceType::Git,
            type: ActivityEventType::GitCommit,
            occurredAt: CarbonImmutable::now(),
            projectRepository: $projectRepository,
        );

        $event = RecordActivityEvent::make()->handle($data, $activeSession);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->project_id)->toBe($projectRepository->project_id)
            ->and($event->project_repository_id)->toBe($projectRepository->id)
            ->and($event->session_id)->toBe($activeSession->id);
    });

    it('dispatches ActivityDetected for every recorded event', function () {
        $projectRepository = ProjectRepository::factory()->create();

        $data = new ActivityEventData(
            sourceType: ActivityEventSourceType::Git,
            type: ActivityEventType::GitCommit,
            occurredAt: CarbonImmutable::now(),
            projectRepository: $projectRepository,
        );

        RecordActivityEvent::make()->handle($data);

        Event::assertDispatched(ActivityDetected::class);
    });
});
