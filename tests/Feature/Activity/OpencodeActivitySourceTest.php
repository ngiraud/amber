<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Data\ActivitySourceConfigs\OpencodeSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\OpencodeActivitySource;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use PDO;

pest()->group('activity', 'sources', 'opencode');

function opencodeTestBase(): string
{
    return storage_path('framework/testing/opencode-activity');
}

beforeEach(function () {
    $settings = app(ActivitySourceSettings::class);
    $settings->opencode = OpencodeSourceConfig::fromArray(['enabled' => true, 'projects_path' => opencodeTestBase()]);
    File::makeDirectory(opencodeTestBase(), recursive: true, force: true);
});

afterEach(function () {
    File::deleteDirectory(opencodeTestBase());
});

it('returns opencode as the identifier', function () {
    expect(app(OpencodeActivitySource::class)->identifier())->toBe(ActivityEventSourceType::Opencode);
});

it('detects Opencode events from SQLite database', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);
    $dbPath = opencodeTestBase().'/opencode.db';

    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->exec('CREATE TABLE session (id TEXT PRIMARY KEY, directory TEXT, title TEXT, time_created INTEGER, time_updated INTEGER)');
    $pdo->exec('CREATE TABLE message (id TEXT PRIMARY KEY, session_id TEXT, time_created INTEGER, time_updated INTEGER, data TEXT)');

    $sessionId = 'ses_123';
    $nowMs = round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session (id, directory, title, time_created, time_updated) VALUES (?, ?, ?, ?, ?)')
        ->execute([$sessionId, $cwd, 'Test Session', $thirtyMinsAgoMs, $nowMs]);

    $messageData = json_encode([
        'role' => 'user',
        'content' => 'Create a file',
        'summary' => [
            'diffs' => [
                ['file' => 'test.md', 'status' => 'added', 'additions' => 1, 'deletions' => 0],
            ],
        ],
    ]);

    $pdo->prepare('INSERT INTO message (id, session_id, time_created, time_updated, data) VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', $sessionId, $thirtyMinsAgoMs + 1000, $nowMs, $messageData]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(3) // 1 SessionStart + 1 UserPrompt + 1 FileTouch
        ->and($events[0]->type)->toBe(ActivityEventType::OpencodeSessionStart)
        ->and($events[1]->type)->toBe(ActivityEventType::OpencodeUserPrompt)
        ->and($events[2]->type)->toBe(ActivityEventType::OpencodeFileTouch)
        ->and($events[2]->metadata['file_path'])->toBe('test.md');
});
