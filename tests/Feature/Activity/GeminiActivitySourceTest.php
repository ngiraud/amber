<?php

declare(strict_types=1);

use App\Data\ActivitySourceConfigs\GeminiSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\GeminiActivitySource;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

pest()->group('activity', 'sources', 'gemini');

function geminiTestBase(): string
{
    return storage_path('framework/testing/gemini-activity');
}

beforeEach(function () {
    $settings = app(ActivitySourceSettings::class);
    $settings->gemini = GeminiSourceConfig::fromArray(['enabled' => true, 'projects_path' => geminiTestBase()]);
    File::makeDirectory(geminiTestBase(), recursive: true, force: true);
});

afterEach(function () {
    File::deleteDirectory(geminiTestBase());
    File::delete(geminiTestBase().'/../projects.json');
});

it('returns gemini as the identifier', function () {
    expect(app(GeminiActivitySource::class)->identifier())->toBe(ActivityEventSourceType::Gemini);
});

it('detects GeminiSessionStart and UserPrompt', function () {
    $cwd = '/tmp/test-gemini';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);
    $projectName = 'test-gemini-project';

    // Mock projects.json
    File::put(geminiTestBase().'/../projects.json', json_encode([
        'projects' => [$cwd => $projectName],
    ]));

    $timestamp = CarbonImmutable::now()->subMinutes(30)->toIso8601ZuluString();
    $sessionId = 'session-123';

    $chatData = [
        'sessionId' => $sessionId,
        'messages' => [
            [
                'timestamp' => $timestamp,
                'type' => 'user',
                'content' => [['text' => 'Hello Gemini']],
            ],
        ],
    ];

    $chatDir = geminiTestBase().'/'.$projectName.'/chats';
    File::makeDirectory($chatDir, recursive: true, force: true);
    File::put($chatDir.'/session.json', json_encode($chatData));

    $events = app(GeminiActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(2)
        ->and($events[0]->type)->toBe(ActivityEventType::GeminiSessionStart)
        ->and($events[1]->type)->toBe(ActivityEventType::GeminiUserPrompt)
        ->and($events[1]->metadata['prompt'])->toBe('Hello Gemini');
});

it('detects GeminiFileTouch from toolCalls', function () {
    $cwd = '/tmp/test-gemini-tools';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);
    $projectName = 'test-gemini-tools-project';

    File::put(geminiTestBase().'/../projects.json', json_encode([
        'projects' => [$cwd => $projectName],
    ]));

    $chatData = [
        'sessionId' => 'session-tools',
        'messages' => [
            [
                'timestamp' => CarbonImmutable::now()->subMinutes(10)->toIso8601ZuluString(),
                'type' => 'gemini',
                'toolCalls' => [
                    [
                        'name' => 'replace',
                        'args' => ['file_path' => 'src/App.php'],
                    ],
                ],
            ],
        ],
    ];

    $chatDir = geminiTestBase().'/'.$projectName.'/chats';
    File::makeDirectory($chatDir, recursive: true, force: true);
    File::put($chatDir.'/session.json', json_encode($chatData));

    $events = app(GeminiActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    // 1 SessionStart (at index 0 message) + 1 FileTouch
    expect($events)->toHaveCount(2)
        ->and($events[1]->type)->toBe(ActivityEventType::GeminiFileTouch)
        ->and($events[1]->metadata['file_path'])->toBe('src/App.php');
});
