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

pest()->group('activity', 'sources');

describe('GitActivitySource', function () {
    it('returns git as the identifier', function () {
        expect((new GitActivitySource)->identifier())->toBe(ActivityEventSourceType::Git);
    });

    it('is available when git command succeeds', function () {
        Process::fake(['git --version' => Process::result('git version 2.39.0')]);

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

        Process::fake(['*' => Process::result('', exitCode: 128)]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(0);
    });

    it('scans commits from a git repository', function () {
        $repo = ProjectRepository::factory()->create(['local_path' => '/some/project']);

        Process::fake([
            '*' => Process::result('abc123|test@example.com|2026-01-01T12:00:00+00:00|Initial commit'),
        ]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(1)
            ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
            ->and($events->first()->type)->toBe(ActivityEventType::GitCommit)
            ->and($events->first()->projectRepository->id)->toBe($repo->id)
            ->and($events->first()->metadata['hash'])->toBe('abc123')
            ->and($events->first()->metadata['author_email'])->toBe('test@example.com');
    });

    it('filters commits by author email when configured from config', function () {
        Config::set('activity.sources.git.author_emails', 'fromconfig@example.com');

        ProjectRepository::factory()->create(['local_path' => '/some/project']);

        Process::fake([
            '*' => Process::result(implode("\n", [
                'abc123|other@example.com|2024-01-01T12:00:00+00:00|Some commit',
                'abc123|fromconfig@example.com|2024-01-01T12:00:00+00:00|Some commit',
            ])),
        ]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(1)
            ->and($events->first()->metadata['author_email'])->toBe('fromconfig@example.com');
    });

    it('filters commits by author email when configured from AppSetting', function () {
        AppSetting::set('git_author_emails', ['fromapp@example.com', 'john@example.com']);

        ProjectRepository::factory()->create(['local_path' => '/some/project']);

        Process::fake([
            '*' => Process::result(implode("\n", [
                'abc123|other@example.com|2024-01-01T12:00:00+00:00|Some commit',
                'abc123|fromapp@example.com|2024-01-01T12:00:00+00:00|Some commit',
            ])),
        ]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(1)
            ->and($events->first()->metadata['author_email'])->toBe('fromapp@example.com');
    });
});
