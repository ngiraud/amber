<?php

declare(strict_types=1);

use App\Actions\Activity\RecordActivityEvent;
use App\Actions\Activity\ScanAllSources;
use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'activity');

function makeSource(string $identifier, bool $available, array $events): ActivitySource
{
    return new class($identifier, $available, $events) implements ActivitySource
    {
        public function __construct(
            private string $id,
            private bool $available,
            private array $events,
        ) {}

        public function identifier(): string
        {
            return $this->id;
        }

        public function isAvailable(): bool
        {
            return $this->available;
        }

        public function scan(CarbonImmutable $since, Illuminate\Support\Collection $repos): Illuminate\Support\Collection
        {
            return collect($this->events);
        }
    };
}

describe('ScanAllSources', function () {
    beforeEach(fn () => Event::fake());

    it('records events from available sources during an active session', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);
        $now = CarbonImmutable::now();

        $source = makeSource('git', available: true, events: [
            new ActivityEventData(
                type: ActivityEventType::GitCommit,
                sourceType: 'git',
                occurredAt: $now,
                project: $project->id,
            ),
        ]);

        $action = new ScanAllSources(RecordActivityEvent::make(), [$source]);
        $events = $action->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1)
            ->and($events->first())->toBeInstanceOf(ActivityEvent::class);
    });

    it('records events with null session_id when no active session exists', function () {
        $project = Project::factory()->create();
        $now = CarbonImmutable::now();

        $source = makeSource('git', available: true, events: [
            new ActivityEventData(
                type: ActivityEventType::GitCommit,
                sourceType: 'git',
                occurredAt: $now,
                project: $project->id,
            ),
        ]);

        $action = new ScanAllSources(RecordActivityEvent::make(), [$source]);
        $events = $action->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1)
            ->and($events->first()->session_id)->toBeNull();
    });

    it('skips unavailable sources', function () {
        $source = makeSource('git', available: false, events: [
            new ActivityEventData(
                type: ActivityEventType::GitCommit,
                sourceType: 'git',
                occurredAt: CarbonImmutable::now(),
            ),
        ]);

        $action = new ScanAllSources(RecordActivityEvent::make(), [$source]);
        $events = $action->handle(CarbonImmutable::now()->subMinutes(10));

        expect($events)->toHaveCount(0);
    });

    it('deduplicates events with the same type, occurred_at, and sourceType', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id, 'ended_at' => null]);
        $now = CarbonImmutable::parse('2026-01-01T12:00:00Z');

        $duplicate = new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: 'git',
            occurredAt: $now,
            project: $project->id,
        );

        $source = makeSource('git', available: true, events: [$duplicate, $duplicate]);

        $action = new ScanAllSources(RecordActivityEvent::make(), [$source]);
        $events = $action->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1);
    });
});
