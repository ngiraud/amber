<?php

declare(strict_types=1);

use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Events\ActivityDetected;
use App\Models\ActivityEvent;
use App\Models\Project;
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

    it('creates ActivityEvent with null session_id when no active session', function () {
        $project = Project::factory()->create();

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            project: $project->id,
        );

        $event = RecordActivityEvent::make()->handle($data);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->session_id)->toBeNull();

        $this->assertDatabaseHas('activity_events', [
            'project_id' => $project->id,
            'session_id' => null,
        ]);
    });

    it('creates ActivityEvent with null session_id when active session is for a different project', function () {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        Session::factory()->create(['project_id' => $otherProject->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            project: $project->id,
        );

        $event = RecordActivityEvent::make()->handle($data);

        expect($event)->toBeInstanceOf(ActivityEvent::class)
            ->and($event->session_id)->toBeNull();
    });

    it('creates an ActivityEvent and links to active session', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            metadata: ['hash' => 'abc123'],
            project: $project->id,
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

    it('dispatches ActivityDetected for every recorded event', function () {
        $project = Project::factory()->create();

        $data = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: CarbonImmutable::now(),
            project: $project->id,
        );

        RecordActivityEvent::make()->handle($data);

        Event::assertDispatched(ActivityDetected::class);
    });
});
