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

function opencodeTestDb(): PDO
{
    $dbPath = opencodeTestBase().'/opencode.db';
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec('CREATE TABLE session (id TEXT PRIMARY KEY, directory TEXT, title TEXT, time_created INTEGER, time_updated INTEGER)');
    $pdo->exec('CREATE TABLE message (id TEXT PRIMARY KEY, session_id TEXT, time_created INTEGER, time_updated INTEGER, data TEXT)');
    $pdo->exec('CREATE TABLE part (id TEXT PRIMARY KEY, message_id TEXT, session_id TEXT, time_created INTEGER, time_updated INTEGER, data TEXT)');

    return $pdo;
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

it('detects a session start event', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'My Session', $thirtyMinsAgoMs, $nowMs]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events[0]->type)->toBe(ActivityEventType::OpencodeSessionStart)
        ->and($events[0]->metadata['session_id'])->toBe('ses_1')
        ->and($events[0]->metadata['title'])->toBe('My Session');
});

it('detects user prompt events from text parts linked to user messages', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Test', $thirtyMinsAgoMs, $nowMs]);

    $userMessageData = json_encode(['role' => 'user', 'agent' => 'build']);
    $pdo->prepare('INSERT INTO message VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', 'ses_1', $thirtyMinsAgoMs, $nowMs, $userMessageData]);

    $partData = json_encode(['type' => 'text', 'text' => 'Create a test file please']);
    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_1', 'msg_1', 'ses_1', $thirtyMinsAgoMs + 500, $nowMs, $partData]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    $promptEvents = $events->filter(fn ($e) => $e->type === ActivityEventType::OpencodeUserPrompt)->values();

    expect($promptEvents)->toHaveCount(1)
        ->and($promptEvents[0]->metadata['prompt'])->toBe('Create a test file please')
        ->and($promptEvents[0]->metadata['session_id'])->toBe('ses_1');
});

it('does not emit user prompt for text parts linked to assistant messages', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Test', $thirtyMinsAgoMs, $nowMs]);

    $assistantMessageData = json_encode(['role' => 'assistant']);
    $pdo->prepare('INSERT INTO message VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', 'ses_1', $thirtyMinsAgoMs, $nowMs, $assistantMessageData]);

    $partData = json_encode(['type' => 'text', 'text' => 'Here is the file you requested.']);
    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_1', 'msg_1', 'ses_1', $thirtyMinsAgoMs + 500, $nowMs, $partData]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    $promptEvents = $events->filter(fn ($e) => $e->type === ActivityEventType::OpencodeUserPrompt);

    expect($promptEvents)->toBeEmpty();
});

it('detects file touch events from patch parts', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Test', $thirtyMinsAgoMs, $nowMs]);

    $pdo->prepare('INSERT INTO message VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', 'ses_1', $thirtyMinsAgoMs, $nowMs, json_encode(['role' => 'assistant'])]);

    $patchData = json_encode([
        'type' => 'patch',
        'hash' => 'abc123def456',
        'files' => ['/tmp/test-opencode/src/Foo.php', '/tmp/test-opencode/src/Bar.php'],
    ]);
    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_1', 'msg_1', 'ses_1', $thirtyMinsAgoMs + 1000, $nowMs, $patchData]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    $fileTouchEvents = $events->filter(fn ($e) => $e->type === ActivityEventType::OpencodeFileTouch)->values();

    expect($fileTouchEvents)->toHaveCount(2)
        ->and($fileTouchEvents[0]->metadata['file_path'])->toBe('/tmp/test-opencode/src/Foo.php')
        ->and($fileTouchEvents[0]->metadata['patch_hash'])->toBe('abc123def456')
        ->and($fileTouchEvents[1]->metadata['file_path'])->toBe('/tmp/test-opencode/src/Bar.php');
});

it('emits one file touch event per file in a patch', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Test', $thirtyMinsAgoMs, $nowMs]);

    $pdo->prepare('INSERT INTO message VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', 'ses_1', $thirtyMinsAgoMs, $nowMs, json_encode(['role' => 'assistant'])]);

    $patch1 = json_encode(['type' => 'patch', 'hash' => 'hash1', 'files' => ['/tmp/test-opencode/a.php']]);
    $patch2 = json_encode(['type' => 'patch', 'hash' => 'hash2', 'files' => ['/tmp/test-opencode/b.php', '/tmp/test-opencode/c.php']]);

    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_1', 'msg_1', 'ses_1', $thirtyMinsAgoMs + 1000, $nowMs, $patch1]);
    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_2', 'msg_1', 'ses_1', $thirtyMinsAgoMs + 2000, $nowMs, $patch2]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    $fileTouchEvents = $events->filter(fn ($e) => $e->type === ActivityEventType::OpencodeFileTouch);

    expect($fileTouchEvents)->toHaveCount(3);
});

it('ignores sessions not matching any repository', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => '/some/other/path']);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $thirtyMinsAgoMs = $nowMs - (30 * 60 * 1000);

    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Test', $thirtyMinsAgoMs, $nowMs]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toBeEmpty();
});

it('ignores events older than the since date', function () {
    $cwd = '/tmp/test-opencode';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $pdo = opencodeTestDb();
    $nowMs = (int) round(microtime(true) * 1000);
    $twoHoursAgoMs = $nowMs - (2 * 60 * 60 * 1000);
    $ninetyMinsAgoMs = $nowMs - (90 * 60 * 1000);

    // Session updated 90 mins ago, but created 2 hours ago
    $pdo->prepare('INSERT INTO session VALUES (?, ?, ?, ?, ?)')
        ->execute(['ses_1', $cwd, 'Old Session', $twoHoursAgoMs, $ninetyMinsAgoMs]);

    $pdo->prepare('INSERT INTO message VALUES (?, ?, ?, ?, ?)')
        ->execute(['msg_1', 'ses_1', $twoHoursAgoMs, $twoHoursAgoMs, json_encode(['role' => 'user'])]);

    // Part created 2 hours ago — before our $since of 1 hour ago
    $oldPartData = json_encode(['type' => 'text', 'text' => 'Old prompt']);
    $pdo->prepare('INSERT INTO part VALUES (?, ?, ?, ?, ?, ?)')
        ->execute(['prt_1', 'msg_1', 'ses_1', $twoHoursAgoMs, $twoHoursAgoMs, $oldPartData]);

    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    // Session start is older than $since, so no events at all
    expect($events)->toBeEmpty();
});

it('returns empty collection when database does not exist', function () {
    $events = app(OpencodeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toBeEmpty();
});
