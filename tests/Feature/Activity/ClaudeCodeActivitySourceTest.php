<?php

declare(strict_types=1);

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\ClaudeCodeActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;

pest()->group('activity', 'sources', 'claude-code');

function makeClaudeSource(string $projectsPath): ClaudeCodeActivitySource
{
    Config::set('activity.sources.claude-code.projects_path', $projectsPath);

    return new ClaudeCodeActivitySource;
}

it('returns claude-code as the identifier', function () {
    expect((new ClaudeCodeActivitySource)->identifier())->toBe(ActivityEventSourceType::ClaudeCode);
});

it('is not available when projects path does not exist', function () {
    $source = makeClaudeSource('/nonexistent/path/that/does/not/exist');

    expect($source->isAvailable())->toBeFalse();
});

it('is available when projects path exists', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    mkdir($tmpDir);

    $source = makeClaudeSource($tmpDir);

    expect($source->isAvailable())->toBeTrue();

    rmdir($tmpDir);
});

it('returns empty collection when projects path is empty', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    mkdir($tmpDir);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);

    rmdir($tmpDir);
});

it('detects ClaudeSessionStart from system/local_command entries', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-project';
    mkdir($projectDir, recursive: true);

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

    file_put_contents($projectDir.'/'.$sessionId.'.jsonl', $jsonl);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeSessionStart)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['session_id'])->toBe($sessionId);

    exec("rm -rf {$tmpDir}");
});

it('detects ClaudeFileTouch from assistant Edit/Write tool use', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-project2';
    mkdir($projectDir, recursive: true);

    $cwd = '/tmp/test-project2';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);

    $timestamp = CarbonImmutable::now()->subMinutes(10)->toIso8601ZuluString();
    $filePath = $cwd.'/src/App.php';

    $line = json_encode([
        'type' => 'assistant',
        'timestamp' => $timestamp,
        'cwd' => $cwd,
        'message' => [
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'tool_use',
                    'name' => 'Edit',
                    'input' => ['file_path' => $filePath, 'old_string' => 'foo', 'new_string' => 'bar'],
                ],
            ],
        ],
    ])."\n";

    file_put_contents($projectDir.'/session.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeFileTouch)
        ->and($events->first()->metadata['tool'])->toBe('Edit')
        ->and($events->first()->projectRepository->id)->toBe($repo->id);

    exec("rm -rf {$tmpDir}");
});

it('skips events that occurred before the since timestamp', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-project3';
    mkdir($projectDir, recursive: true);

    $cwd = '/tmp/test-project3';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $oldTimestamp = CarbonImmutable::now()->subHours(2)->toIso8601ZuluString();

    $line = json_encode([
        'type' => 'system',
        'subtype' => 'local_command',
        'timestamp' => $oldTimestamp,
        'cwd' => $cwd,
        'sessionId' => 'old-session',
    ])."\n";

    file_put_contents($projectDir.'/old-session.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);

    exec("rm -rf {$tmpDir}");
});

it('skips files where cwd does not match any repository', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/some-project';
    mkdir($projectDir, recursive: true);

    $timestamp = CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString();

    $line = json_encode([
        'type' => 'system',
        'subtype' => 'local_command',
        'timestamp' => $timestamp,
        'cwd' => '/no/matching/repository',
        'sessionId' => 'no-match',
    ])."\n";

    file_put_contents($projectDir.'/no-match.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);

    exec("rm -rf {$tmpDir}");
});

it('detects ClaudeUserPrompt from human entries with string content', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-prompt-str';
    mkdir($projectDir, recursive: true);

    $cwd = '/tmp/test-prompt-str';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);

    $timestamp = CarbonImmutable::now()->subMinutes(10)->toIso8601ZuluString();
    $sessionId = 'session-'.uniqid();

    $line = json_encode([
        'type' => 'human',
        'timestamp' => $timestamp,
        'cwd' => $cwd,
        'sessionId' => $sessionId,
        'message' => [
            'role' => 'user',
            'content' => 'Add a login button to the navbar',
        ],
    ])."\n";

    file_put_contents($projectDir.'/session.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeUserPrompt)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['session_id'])->toBe($sessionId)
        ->and($events->first()->metadata['prompt'])->toBe('Add a login button to the navbar');

    exec("rm -rf {$tmpDir}");
});

it('detects ClaudeUserPrompt from human entries with array content blocks', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-prompt-arr';
    mkdir($projectDir, recursive: true);

    $cwd = '/tmp/test-prompt-arr';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $timestamp = CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString();

    $line = json_encode([
        'type' => 'human',
        'timestamp' => $timestamp,
        'cwd' => $cwd,
        'sessionId' => 'session-abc',
        'message' => [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Refactor the UserController to use actions'],
            ],
        ],
    ])."\n";

    file_put_contents($projectDir.'/session.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::ClaudeUserPrompt)
        ->and($events->first()->metadata['prompt'])->toBe('Refactor the UserController to use actions');

    exec("rm -rf {$tmpDir}");
});

it('truncates long prompts to 500 characters', function () {
    $tmpDir = sys_get_temp_dir().'/claude-projects-'.uniqid();
    $projectDir = $tmpDir.'/-tmp-test-prompt-long';
    mkdir($projectDir, recursive: true);

    $cwd = '/tmp/test-prompt-long';
    ProjectRepository::factory()->create(['local_path' => $cwd]);

    $timestamp = CarbonImmutable::now()->subMinutes(5)->toIso8601ZuluString();
    $longPrompt = str_repeat('a', 600);

    $line = json_encode([
        'type' => 'human',
        'timestamp' => $timestamp,
        'cwd' => $cwd,
        'sessionId' => 'session-long',
        'message' => ['role' => 'user', 'content' => $longPrompt],
    ])."\n";

    file_put_contents($projectDir.'/session.jsonl', $line);

    $source = makeClaudeSource($tmpDir);
    $events = $source->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events->first()->metadata['prompt'])->toHaveLength(500);

    exec("rm -rf {$tmpDir}");
});
