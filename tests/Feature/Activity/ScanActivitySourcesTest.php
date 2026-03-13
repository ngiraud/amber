<?php

declare(strict_types=1);

use App\Actions\Activity\ScanActivitySources;
use App\Data\ActivityEventData;
use App\Data\ActivitySourceConfigs\ClaudeCodeSourceConfig;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Data\ActivitySourceConfigs\GitHubSourceConfig;
use App\Data\ActivitySourceConfigs\GitSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Models\Session;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\FakeActivitySource;

pest()->group('actions', 'activity');

function mockSource(mixed $source): void
{
    ScanActivitySources::fakePartial()
        ->shouldReceive('discoverSources')
        ->andReturn(collect([$source]));
}

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

    $events = app(ScanActivitySources::class)->handle($now->subMinutes(10), $now);

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

    $events = app(ScanActivitySources::class)->handle($now->subMinutes(10), $now);

    expect($events)->toHaveCount(1)
        ->and($events->first()->session_id)->toBeNull();
});

it('skips unavailable sources', function () {
    $now = CarbonImmutable::now();

    mockSource(app(FakeActivitySource::class)->setAvailable(false));

    $events = app(ScanActivitySources::class)->handle($now->subMinutes(10), $now);

    expect($events)->toHaveCount(0);
});

it('discoverSources skips disabled sources', function () {
    $settings = app(ActivitySourceSettings::class);
    $settings->git = GitSourceConfig::fromArray(['enabled' => false, 'author_emails' => []]);
    $settings->github = GitHubSourceConfig::fromArray(['enabled' => false, 'username' => null]);
    $settings->claude_code = ClaudeCodeSourceConfig::fromArray(['enabled' => false, 'projects_path' => '~/.claude/projects']);
    $settings->fswatch = FswatchSourceConfig::fromArray(['enabled' => false, 'debounce_seconds' => 3, 'excluded_patterns' => [], 'allowed_extensions' => []]);

    $sources = app(ScanActivitySources::class)->discoverSources();

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

    $events = app(ScanActivitySources::class)->handle($now->subMinutes(10), $now);

    expect($events)->toHaveCount(1);
});
