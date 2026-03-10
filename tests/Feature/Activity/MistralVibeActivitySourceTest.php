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

it('detects VibeSessionStart and UserPrompt', function () {
    $cwd = '/tmp/test-vibe';
    $repo = ProjectRepository::factory()->create(['local_path' => $cwd]);
    $sessionId = 'vibe-123';

    $sessionDir = vibeTestBase().'/'.$sessionId;
    File::makeDirectory($sessionDir, recursive: true, force: true);

    $meta = [
        'session_id' => $sessionId,
        'start_time' => CarbonImmutable::now()->subMinutes(30)->toIso8601ZuluString(),
        'environment' => [
            'working_directory' => $cwd,
        ],
    ];

    $messages = [
        ['role' => 'user', 'content' => 'Hello Vibe'],
        ['role' => 'assistant', 'content' => 'Hello!', 'tool_calls' => [
            [
                'function' => [
                    'name' => 'write_file',
                    'arguments' => json_encode(['path' => 'test.txt']),
                ],
            ],
        ]],
    ];

    File::put($sessionDir.'/meta.json', json_encode($meta));
    $jsonl = implode("\n", array_map(fn ($m) => json_encode($m), $messages));
    File::put($sessionDir.'/messages.jsonl', $jsonl);

    $events = app(MistralVibeActivitySource::class)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(3)
        ->and($events[0]->type)->toBe(ActivityEventType::VibeSessionStart)
        ->and($events[1]->type)->toBe(ActivityEventType::VibeUserPrompt)
        ->and($events[2]->type)->toBe(ActivityEventType::VibeFileTouch)
        ->and($events[2]->metadata['file_path'])->toBe('test.txt');
});
