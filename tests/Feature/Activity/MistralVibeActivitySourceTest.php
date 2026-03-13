<?php

declare(strict_types=1);

use App\Data\ActivitySourceConfigs\MistralVibeSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\MistralVibeActivitySource;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

pest()->group('activity', 'sources', 'vibe');

function vibeTestBase(): string
{
    return storage_path('framework/testing/vibe-activity');
}

function vibeSession(string $id, array $meta, array $messages): void
{
    $sessionDir = vibeTestBase().'/'.$id;
    File::makeDirectory($sessionDir, recursive: true, force: true);
    File::put($sessionDir.'/meta.json', json_encode($meta));
    File::put($sessionDir.'/messages.jsonl', implode("\n", array_map(fn ($m) => json_encode($m), $messages)));
}

beforeEach(function () {
    $settings = app(ActivitySourceSettings::class);
    $settings->mistral_vibe = MistralVibeSourceConfig::fromArray(['enabled' => true, 'projects_path' => vibeTestBase()]);
    File::makeDirectory(vibeTestBase(), recursive: true, force: true);
});

afterEach(function () {
    File::deleteDirectory(vibeTestBase());
});

it('returns mistral_vibe as the identifier', function () {
    expect(app(MistralVibeActivitySource::class)->identifier())->toBe(ActivityEventSourceType::MistralVibe);
});

it('detects a session start event with git metadata', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'git_branch' => 'main',
        'git_commit' => 'abc123',
        'environment' => ['working_directory' => $cwd],
    ], []);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    $start = $events->first(fn ($e) => $e->type === ActivityEventType::VibeSessionStart);

    expect($start)->not->toBeNull()
        ->and($start->metadata['session_id'])->toBe('ses-1')
        ->and($start->metadata['git_branch'])->toBe('main')
        ->and($start->metadata['git_commit'])->toBe('abc123');
});

it('detects a session end event when end_time is present', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $endTime = CarbonImmutable::now()->subMinutes(5);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'end_time' => $endTime->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], []);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    $end = $events->first(fn ($e) => $e->type === ActivityEventType::VibeSessionEnd);

    expect($end)->not->toBeNull()
        ->and($end->metadata['session_id'])->toBe('ses-1')
        ->and($end->occurredAt->timestamp)->toBe($endTime->utc()->timestamp);
});

it('does not emit session end event when end_time is absent', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], []);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    expect($events->filter(fn ($e) => $e->type === ActivityEventType::VibeSessionEnd))->toBeEmpty();
});

it('detects user prompt events', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], [
        ['role' => 'user', 'content' => 'Please refactor the service layer'],
        ['role' => 'assistant', 'tool_calls' => []],
    ]);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    $prompt = $events->first(fn ($e) => $e->type === ActivityEventType::VibeUserPrompt);

    expect($prompt)->not->toBeNull()
        ->and($prompt->metadata['prompt'])->toBe('Please refactor the service layer');
});

it('detects file touch events from search_replace tool calls', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], [
        ['role' => 'user', 'content' => 'Fix the bug'],
        ['role' => 'assistant', 'tool_calls' => [
            ['function' => ['name' => 'search_replace', 'arguments' => json_encode(['file_path' => 'src/Foo.php', 'content' => 'new content'])]],
            ['function' => ['name' => 'search_replace', 'arguments' => json_encode(['file_path' => 'src/Bar.php', 'content' => 'other content'])]],
        ]],
    ]);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    $fileTouches = $events->filter(fn ($e) => $e->type === ActivityEventType::VibeFileTouch)->values();

    expect($fileTouches)->toHaveCount(2)
        ->and($fileTouches[0]->metadata['file_path'])->toBe('src/Foo.php')
        ->and($fileTouches[0]->metadata['tool'])->toBe('search_replace')
        ->and($fileTouches[1]->metadata['file_path'])->toBe('src/Bar.php');
});

it('ignores tool calls that are not file-writing tools', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], [
        ['role' => 'assistant', 'tool_calls' => [
            ['function' => ['name' => 'read_file', 'arguments' => json_encode(['path' => 'src/Foo.php'])]],
            ['function' => ['name' => 'bash', 'arguments' => json_encode(['command' => 'ls -la'])]],
        ]],
    ]);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    expect($events->filter(fn ($e) => $e->type === ActivityEventType::VibeFileTouch))->toBeEmpty();
});

it('ignores sessions not matching any repository', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/other/path']);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601String(),
        'environment' => ['working_directory' => '/tmp/unmatched'],
    ], []);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    expect($events)->toBeEmpty();
});

it('ignores sessions older than the since date', function () {
    $cwd = '/tmp/test-vibe';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    vibeSession('ses-1', [
        'session_id' => 'ses-1',
        'start_time' => CarbonImmutable::now()->subHours(2)->toIso8601String(),
        'environment' => ['working_directory' => $cwd],
    ], [
        ['role' => 'user', 'content' => 'Old message'],
    ]);

    // Touch the meta file to make filemtime recent, but start_time is old
    touch(vibeTestBase().'/ses-1/meta.json');

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), CarbonImmutable::now(), ProjectRepository::all());

    expect($events)->toBeEmpty();
});
