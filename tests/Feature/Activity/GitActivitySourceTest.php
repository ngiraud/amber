<?php

declare(strict_types=1);

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\AppSetting;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\GitActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;

pest()->group('activity', 'sources', 'git');

it('returns git as the identifier', function () {
    expect((new GitActivitySource)->identifier())->toBe(ActivityEventSourceType::Git);
});

it('is available when git command succeeds', function () {
    Process::fake(fn () => Process::result('git version 2.39.0'));

    expect((new GitActivitySource)->isAvailable())->toBeTrue();
});

it('is not available when git is not installed', function () {
    Process::fake(['*' => Process::result('', exitCode: 1)]);

    expect((new GitActivitySource)->isAvailable())->toBeFalse();
});

it('returns empty collection when no repositories are passed', function () {
    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), collect());

    expect($events)->toHaveCount(0);
});

it('returns empty collection when git log fails', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/path']);

    Process::fake(function ($process) {
        if (in_array('log', $process->command)) {
            return Process::result('', exitCode: 128);
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(0);
});

it('scans commits from a git repository with enriched metadata', function () {
    $repo = ProjectRepository::factory()->create(['local_path' => '/some/project']);

    Process::fake(function ($process) {
        if (in_array('rev-parse', $process->command)) {
            return Process::result('main');
        }

        if (in_array('log', $process->command)) {
            return Process::result("---COMMIT---abc123|test@example.com|2026-01-01T12:00:00+00:00|Initial commit\n5\t2\tapp/Models/Foo.php");
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
        ->and($events->first()->type)->toBe(ActivityEventType::GitCommit)
        ->and($events->first()->projectRepository->id)->toBe($repo->id)
        ->and($events->first()->metadata['hash'])->toBe('abc123')
        ->and($events->first()->metadata['author_email'])->toBe('test@example.com')
        ->and($events->first()->metadata['message'])->toBe('Initial commit')
        ->and($events->first()->metadata['branch'])->toBe('main')
        ->and($events->first()->metadata['added_lines'])->toBe(5)
        ->and($events->first()->metadata['removed_lines'])->toBe(2)
        ->and($events->first()->metadata['changed_files'])->toBe(1);
});

it('aggregates numstat across multiple changed files', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $numstat = "10\t3\tapp/Models/Foo.php\n5\t0\tapp/Models/Bar.php\n-\t-\tassets/image.png";

    Process::fake(function ($process) use ($numstat) {
        if (in_array('rev-parse', $process->command)) {
            return Process::result('feature/my-branch');
        }

        if (in_array('log', $process->command)) {
            return Process::result("---COMMIT---abc123|dev@example.com|2026-01-01T12:00:00+00:00|Add feature\n{$numstat}");
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events->first()->metadata['added_lines'])->toBe(15)
        ->and($events->first()->metadata['removed_lines'])->toBe(3)
        ->and($events->first()->metadata['changed_files'])->toBe(3);
});

it('filters commits by author email when configured from config', function () {
    Config::set('activity.sources.git.author_emails', 'fromconfig@example.com');

    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $logOutput = implode('---COMMIT---', [
        '',
        'abc123|other@example.com|2024-01-01T12:00:00+00:00|Some commit',
        'def456|fromconfig@example.com|2024-01-01T12:00:00+00:00|Some commit',
    ]);

    Process::fake(function ($process) use ($logOutput) {
        if (in_array('log', $process->command)) {
            return Process::result($logOutput);
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->metadata['author_email'])->toBe('fromconfig@example.com');
});

it('filters commits by author email when configured from AppSetting', function () {
    AppSetting::set('git_author_emails', ['fromapp@example.com', 'john@example.com']);

    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    $logOutput = implode('---COMMIT---', [
        '',
        'abc123|other@example.com|2024-01-01T12:00:00+00:00|Some commit',
        'def456|fromapp@example.com|2024-01-01T12:00:00+00:00|Some commit',
    ]);

    Process::fake(function ($process) use ($logOutput) {
        if (in_array('log', $process->command)) {
            return Process::result($logOutput);
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->metadata['author_email'])->toBe('fromapp@example.com');
});

it('detects branch switches from git reflog', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    Process::fake(function ($process) {
        if (in_array('reflog', $process->command)) {
            return Process::result('dev@example.com|2026-01-01T12:00:00+00:00|checkout: moving from main to feature/new-ui');
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::GitBranchSwitch)
        ->and($events->first()->metadata['from_branch'])->toBe('main')
        ->and($events->first()->metadata['to_branch'])->toBe('feature/new-ui')
        ->and($events->first()->metadata['author_email'])->toBe('dev@example.com');
});

it('ignores non-checkout reflog entries', function () {
    ProjectRepository::factory()->create(['local_path' => '/some/project']);

    Process::fake(function ($process) {
        if (in_array('reflog', $process->command)) {
            return Process::result(implode("\n", [
                'dev@example.com|2026-01-01T12:00:00+00:00|commit: Initial commit',
                'dev@example.com|2026-01-01T12:01:00+00:00|checkout: moving from main to dev',
            ]));
        }

        return Process::result('');
    });

    $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

    expect($events)->toHaveCount(1)
        ->and($events->first()->type)->toBe(ActivityEventType::GitBranchSwitch);
});
