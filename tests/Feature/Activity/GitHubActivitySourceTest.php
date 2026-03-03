<?php

declare(strict_types=1);

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\AppSetting;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\GitHubActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Process;

pest()->group('activity', 'sources', 'github');

it('returns github as the identifier', function () {
    expect((new GitHubActivitySource)->identifier())->toBe(ActivityEventSourceType::GitHub);
});

it('is available when gh auth status succeeds', function () {
    Process::fake(fn () => Process::result('Logged in to github.com'));

    expect((new GitHubActivitySource)->isAvailable())->toBeTrue();
});

it('is not available when gh is not authenticated', function () {
    Process::fake(['*' => Process::result('', exitCode: 1)]);

    expect((new GitHubActivitySource)->isAvailable())->toBeFalse();
});

it('returns empty collection when no repositories are passed', function () {
    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);
});

it('skips repositories without a GitHub remote', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    Process::fake(function ($process) {
        if (in_array('get-url', $process->command)) {
            return Process::result('', exitCode: 128);
        }

        return Process::result('testuser');
    });

    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});

it('emits GitPrOpened for PRs created after since', function () {
    $repo = ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $createdAt = CarbonImmutable::now()->subMinutes(30)->toIso8601String();

    $prs = json_encode([[
        'number' => 42,
        'title' => 'Add new feature',
        'body' => 'Description',
        'state' => 'OPEN',
        'createdAt' => $createdAt,
        'mergedAt' => null,
        'url' => 'https://github.com/owner/repo/pull/42',
        'headRefName' => 'feature/new-ui',
    ]]);

    Process::fake(function ($process) use ($prs) {
        if (in_array('get-url', $process->command)) {
            return Process::result('git@github.com:owner/repo.git');
        }

        if (in_array('list', $process->command)) {
            return Process::result($prs);
        }

        return Process::result('testuser');
    });

    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::GitPrOpened)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['number'])->toBe(42)
        ->and($events->first()->metadata['title'])->toBe('Add new feature')
        ->and($events->first()->metadata['branch'])->toBe('feature/new-ui')
        ->and($events->first()->metadata['repo'])->toBe('owner/repo');
});

it('emits GitPrMerged for PRs merged after since', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $createdAt = CarbonImmutable::now()->subHours(2)->toIso8601String();
    $mergedAt = CarbonImmutable::now()->subMinutes(15)->toIso8601String();

    $prs = json_encode([[
        'number' => 10,
        'title' => 'Fix critical bug',
        'body' => '',
        'state' => 'MERGED',
        'createdAt' => $createdAt,
        'mergedAt' => $mergedAt,
        'url' => 'https://github.com/owner/repo/pull/10',
        'headRefName' => 'fix/critical-bug',
    ]]);

    Process::fake(function ($process) use ($prs) {
        if (in_array('get-url', $process->command)) {
            return Process::result('https://github.com/owner/repo.git');
        }

        if (in_array('list', $process->command)) {
            return Process::result($prs);
        }

        return Process::result('testuser');
    });

    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    // createdAt is 2 hours ago (before since=1 hour ago) → no GitPrOpened
    // mergedAt is 15 minutes ago (after since) → GitPrMerged
    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::GitPrMerged)
        ->and($events->first()->metadata['number'])->toBe(10)
        ->and($events->first()->metadata)->toHaveKey('merged_at');
});

it('uses github_username from AppSetting when available', function () {
    AppSetting::set('github_username', 'myuser');

    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    Process::fake(function ($process) {
        if (in_array('get-url', $process->command)) {
            return Process::result('git@github.com:owner/repo.git');
        }

        if (in_array('list', $process->command)) {
            return Process::result(json_encode([]));
        }

        return Process::result('');
    });

    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);

    Process::assertRan(fn ($process) => in_array('--author', (array) $process->command) &&
        in_array('myuser', (array) $process->command));
});

it('skips PRs created and merged before the since timestamp', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $oldDate = CarbonImmutable::now()->subHours(3)->toIso8601String();

    $prs = json_encode([[
        'number' => 5,
        'title' => 'Old PR',
        'body' => '',
        'state' => 'MERGED',
        'createdAt' => $oldDate,
        'mergedAt' => $oldDate,
        'url' => 'https://github.com/owner/repo/pull/5',
        'headRefName' => 'old-branch',
    ]]);

    Process::fake(function ($process) use ($prs) {
        if (in_array('get-url', $process->command)) {
            return Process::result('git@github.com:owner/repo.git');
        }

        if (in_array('list', $process->command)) {
            return Process::result($prs);
        }

        return Process::result('testuser');
    });

    $events = (new GitHubActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});
