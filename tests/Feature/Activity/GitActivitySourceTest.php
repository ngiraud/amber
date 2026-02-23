<?php

declare(strict_types=1);

use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\GitActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Process;

pest()->group('activity', 'sources');

describe('GitActivitySource', function () {
    it('returns git as the identifier', function () {
        expect((new GitActivitySource)->identifier())->toBe('git');
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
            '*' => Process::result('abc123|test@example.com|2024-01-01T12:00:00+00:00|Initial commit'),
        ]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(1)
            ->and($events->first())->toBeInstanceOf(ActivityEventData::class)
            ->and($events->first()->type)->toBe(ActivityEventType::GitCommit)
            ->and($events->first()->project)->toBe($repo->project_id)
            ->and($events->first()->metadata['hash'])->toBe('abc123')
            ->and($events->first()->metadata['author_email'])->toBe('test@example.com');
    });

    it('filters commits by author email when configured', function () {
        Config::set('activity.git.author_email', 'test@example.com');

        ProjectRepository::factory()->create(['local_path' => '/some/project']);

        Process::fake([
            '*' => Process::result('abc123|other@example.com|2024-01-01T12:00:00+00:00|Some commit'),
        ]);

        $events = (new GitActivitySource)->scan(CarbonImmutable::now()->subHour(), ProjectRepository::all());

        expect($events)->toHaveCount(0);
    });
});
