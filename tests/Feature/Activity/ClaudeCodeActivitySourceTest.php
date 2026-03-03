<?php

declare(strict_types=1);

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\ClaudeCodeActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

pest()->group('activity', 'sources', 'claude-code');

function claudeTestBase(): string
{
    return storage_path('framework/testing/claude-code-activity');
}

function claudeTestDir(string $name): string
{
    $dir = claudeTestBase().'/'.$name;
    File::makeDirectory($dir, recursive: true, force: true);

    return $dir;
}

beforeEach(function () {
    Config::set('activity.sources.claude-code.projects_path', claudeTestBase());
    File::makeDirectory(claudeTestBase(), recursive: true, force: true);
});

afterEach(function () {
    File::deleteDirectory(claudeTestBase());
});

it('returns claude-code as the identifier', function () {
    expect((new ClaudeCodeActivitySource)->identifier())->toBe(ActivityEventSourceType::ClaudeCode);
});

it('is not available when projects path does not exist', function () {
    Config::set('activity.sources.claude-code.projects_path', '/nonexistent/path/that/does/not/exist');

    expect((new ClaudeCodeActivitySource)->isAvailable())->toBeFalse();
});

it('is available when projects path exists', function () {
    expect((new ClaudeCodeActivitySource)->isAvailable())->toBeTrue();
});

it('returns empty collection when projects path is empty', function () {
    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);
});

it('detects ClaudeSessionStart from system/local_command entries', function () {
    $cwd = '/tmp/test-project';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);

    $timestamp = CarbonImmutable::now()->subMinutes(30)->toIso8601ZuluString();
    $sessionId = 'session-'.uniqid();

    $jsonl = json_encode([
        'type' => 'system',
        'subtype' => 'local_command',
        'timestamp' => $timestamp,
        'cwd' => $cwd,
        'sessionId' => $sessionId,
    ])."\n";

    $file = claudeTestDir('test-project').'/'.$sessionId.'.jsonl';
    File::put($file, $jsonl);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeSessionStart)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['session_id'])->toBe($sessionId)
        ->and($events->first()->metadata['source_file'])->toBe($file);
});

it('detects ClaudeFileTouch from assistant Edit/Write tool use', function () {
    $cwd = '/tmp/test-project2';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'assistant',
        'timestamp' => CarbonImmutable::now()->subMinutes(10)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'message' => [
            'role' => 'assistant',
            'content' => [[
                'type' => 'tool_use',
                'name' => 'Edit',
                'input' => ['file_path' => $cwd.'/src/App.php', 'old_string' => 'foo', 'new_string' => 'bar'],
            ]],
        ],
    ])."\n";

    $file = claudeTestDir('test-project2').'/session.jsonl';
    File::put($file, $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeFileTouch)
        ->and($events->first()->metadata['tool'])->toBe('Edit')
        ->and($events->first()->metadata['source_file'])->toBe($file)
        ->and($events->first()->projectRepository->id)->toBe($repo->id);
});

it('skips events that occurred before the since timestamp', function () {
    $cwd = '/tmp/test-project3';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'system',
        'subtype' => 'local_command',
        'timestamp' => CarbonImmutable::now()->subHours(2)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => 'old-session',
    ])."\n";

    File::put(claudeTestDir('test-project3').'/old-session.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});

it('skips files where cwd does not match any repository', function () {
    $line = json_encode([
        'type' => 'system',
        'subtype' => 'local_command',
        'timestamp' => CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString(),
        'cwd' => '/no/matching/repository',
        'sessionId' => 'no-match',
    ])."\n";

    File::put(claudeTestDir('some-project').'/no-match.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);
});

it('detects ClaudeUserPrompt from human entries with string content', function () {
    $cwd = '/tmp/test-prompt-str';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);

    $sessionId = 'session-'.uniqid();

    $line = json_encode([
        'type' => 'user',
        'timestamp' => CarbonImmutable::now()->subMinutes(10)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => $sessionId,
        'message' => ['role' => 'user', 'content' => 'Add a login button to the navbar'],
    ])."\n";

    $file = claudeTestDir('test-prompt-str').'/session.jsonl';
    File::put($file, $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeUserPrompt)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['session_id'])->toBe($sessionId)
        ->and($events->first()->metadata['prompt'])->toBe('Add a login button to the navbar')
        ->and($events->first()->metadata['source_file'])->toBe($file);
});

it('detects ClaudeUserPrompt from human entries with array content blocks', function () {
    $cwd = '/tmp/test-prompt-arr';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'user',
        'timestamp' => CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => 'session-abc',
        'message' => [
            'role' => 'user',
            'content' => [['type' => 'text', 'text' => 'Refactor the UserController to use actions']],
        ],
    ])."\n";

    File::put(claudeTestDir('test-prompt-arr').'/session.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeUserPrompt)
        ->and($events->first()->metadata['prompt'])->toBe('Refactor the UserController to use actions');
});

it('skips sidechain entries', function () {
    $cwd = '/tmp/test-sidechain';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'user',
        'isSidechain' => true,
        'timestamp' => CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => 'session-sidechain',
        'message' => ['role' => 'user', 'content' => 'This is an internal subagent prompt'],
    ])."\n";

    File::put(claudeTestDir('test-sidechain').'/session.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});

it('skips user entries that contain tool results instead of a prompt', function () {
    $cwd = '/tmp/test-tool-result';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'user',
        'timestamp' => CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => 'session-tool',
        'message' => [
            'role' => 'user',
            'content' => [[
                'tool_use_id' => 'toolu_abc123',
                'type' => 'tool_result',
                'content' => 'some tool output',
            ]],
        ],
    ])."\n";

    File::put(claudeTestDir('test-tool-result').'/session.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});

it('truncates long prompts to 500 characters', function () {
    $cwd = '/tmp/test-prompt-long';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $line = json_encode([
        'type' => 'user',
        'timestamp' => CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString(),
        'cwd' => $cwd,
        'sessionId' => 'session-long',
        'message' => ['role' => 'user', 'content' => str_repeat('a', 600)],
    ])."\n";

    File::put(claudeTestDir('test-prompt-long').'/session.jsonl', $line);

    $events = (new ClaudeCodeActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events->first()->metadata['prompt'])->toHaveLength(500);
});
