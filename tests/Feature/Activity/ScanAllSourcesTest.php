<?php

declare(strict_types=1);

use App\Actions\Activity\ScanAllSources;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeActivitySource;

pest()->group('actions', 'activity');

function mockSource(mixed $source): void
{
    ScanAllSources::fakePartial()
        ->shouldReceive('discoverSources')
        ->andReturn(collect([$source]));
}

describe('ScanAllSources', function () {
    beforeEach(fn () => Event::fake());

    it('records events from available sources during an active session', function () {
        $projectRepository = ProjectRepository::factory()->create();
        $session = Session::factory()->create(['project_id' => $projectRepository->project_id, 'ended_at' => null]);
        $now = CarbonImmutable::now();

        mockSource(app(FakeActivitySource::class)->setEvents([
            new ActivityEventData(
                sourceType: ActivityEventSourceType::Git,
                type: ActivityEventType::GitCommit,
                occurredAt: $now,
                projectRepository: $projectRepository,
            ),
        ]));

        $events = app(ScanAllSources::class)->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1)
            ->and($events->first())->toBeInstanceOf(ActivityEvent::class)
            ->and($events->first()->session_id)->toBe($session->id);
    });

    it('records events with null session_id when no active session exists', function () {
        $projectRepository = ProjectRepository::factory()->create();
        $now = CarbonImmutable::now();

        mockSource(app(FakeActivitySource::class)->setEvents([
            new ActivityEventData(
                sourceType: ActivityEventSourceType::Git,
                type: ActivityEventType::GitCommit,
                occurredAt: $now,
                projectRepository: $projectRepository,
            ),
        ]));

        $events = app(ScanAllSources::class)->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1)
            ->and($events->first()->session_id)->toBeNull();
    });

    it('skips unavailable sources', function () {
        $now = CarbonImmutable::now();

        mockSource(app(FakeActivitySource::class)->setAvailable(false));

        $events = app(ScanAllSources::class)->handle($now->subMinutes(10));

        expect($events)->toHaveCount(0);
    });

    it('discoverSources skips disabled sources', function () {
        Config::set('activity.sources.git.enabled', false);
        Config::set('activity.sources.github.enabled', false);
        Config::set('activity.sources.claude-code.enabled', false);
        Config::set('activity.sources.fswatch.enabled', false);

        $sources = app(ScanAllSources::class)->discoverSources();

        expect($sources)->toHaveCount(0);
    });

    it('deduplicates events with the same type, occurred_at, and sourceType', function () {
        $projectRepository = ProjectRepository::factory()->create();
        Session::factory()->create(['project_id' => $projectRepository->project_id, 'ended_at' => null]);
        $now = CarbonImmutable::parse('2026-01-01T12:00:00Z');

        $duplicatedEvent = new ActivityEventData(
            sourceType: ActivityEventSourceType::Git,
            type: ActivityEventType::GitCommit,
            occurredAt: $now,
            projectRepository: $projectRepository,
        );

        mockSource(app(FakeActivitySource::class)->setEvents([$duplicatedEvent, $duplicatedEvent]));

        $events = app(ScanAllSources::class)->handle($now->subMinutes(10));

        expect($events)->toHaveCount(1);
    });
});
