<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Symfony\Component\Process\Process as SymfonyProcess;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\title;
use function Laravel\Prompts\warning;

class ReleaseCommand extends Command
{
    protected $signature = 'release';

    protected $description = 'Create a new release interactively';

    public function handle(): int
    {
        intro('Amber Release');

        if (! $this->preflight()) {
            return self::FAILURE;
        }

        $currentVersion = $this->currentVersion();

        $this->showCurrentState($currentVersion);

        if (! $this->runTests()) {
            return self::FAILURE;
        }

        $newVersion = $this->selectVersion($currentVersion);

        if (! confirm("Create and push tag v{$newVersion}?")) {
            warning('Release cancelled.');

            return self::SUCCESS;
        }

        $this->publish($newVersion);

        outro("v{$newVersion} released. GitHub Actions is building the artifacts.");

        return self::SUCCESS;
    }

    private function preflight(): bool
    {
        title('Preflight Checks');

        $branch = mb_trim(Process::run(['git', 'rev-parse', '--abbrev-ref', 'HEAD'])->output());
        $status = mb_trim(Process::run(['git', 'status', '--porcelain'])->output());
        Process::run(['git', 'fetch', 'origin', '--quiet']);
        $behind = (int) mb_trim(Process::run(['git', 'rev-list', 'HEAD..origin/main', '--count'])->output());

        $passed = true;

        task('On main branch', fn (): bool => $branch === 'main');

        if ($branch !== 'main') {
            error("Current branch: {$branch}");
            $passed = false;
        }

        task('Working tree clean', fn (): bool => $status === '');

        if ($status !== '' && ! confirm('Uncommitted changes detected. Release anyway?', default: false)) {
            $passed = false;
        }

        task('Up to date with origin', fn (): bool => $behind === 0);

        if ($behind > 0) {
            error("Branch is {$behind} commit(s) behind origin/main. Run git pull first.");
            $passed = false;
        }

        return $passed;
    }

    private function showCurrentState(string $currentVersion): void
    {
        title('Current State');
        info("Current version: v{$currentVersion}");

        $base = mb_trim(Process::run('git describe --tags --abbrev=0 2>/dev/null || git rev-list --max-parents=0 HEAD')->output());
        $log = Process::run(['git', 'log', "{$base}..HEAD", '--pretty=format:%h|%s|%cr'])->output();

        $commits = array_map(
            fn (string $line): array => explode('|', $line, 3),
            array_filter(explode("\n", mb_trim($log)))
        );

        if ($commits !== []) {
            table(['Hash', 'Message', 'When'], $commits);
        } else {
            warning('No commits since last tag.');
        }
    }

    private function runTests(): bool
    {
        title('Test Suite');
        info('Running composer test:all...');

        $pending = SymfonyProcess::isTtySupported()
            ? Process::tty()
            : Process::newPendingProcess();

        $result = $pending->run('composer test:all');

        if (! $result->successful()) {
            error('Tests failed. Fix issues before releasing.');

            return false;
        }

        info('All checks passed.');

        return true;
    }

    private function publish(string $version): void
    {
        title('Release');

        task('Update CHANGELOG.md', function () use ($version): bool {
            $this->updateChangelog($version);

            return true;
        });

        task("Commit, tag v{$version}, and push", function () use ($version): bool {
            Process::run(['git', 'add', 'CHANGELOG.md']);
            Process::run(['git', 'commit', '-m', "chore: release v{$version} [skip ci]"]);
            Process::run(['git', 'push', 'origin', 'main', '--quiet']);
            Process::run(['git', 'tag', "v{$version}"]);

            return Process::run(['git', 'push', 'origin', "v{$version}", '--quiet'])->successful();
        });
    }

    private function changelogPath(): string
    {
        return (string) config('changelog.path', base_path('CHANGELOG.md'));
    }

    private function currentVersion(): string
    {
        $changelog = (string) file_get_contents($this->changelogPath());

        if (preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', $changelog, $matches)) {
            return $matches[1];
        }

        return '0.0.0';
    }

    private function selectVersion(string $current): string
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $current));

        $choice = select(
            label: 'Select release type',
            options: [
                'patch' => "Patch — v{$major}.{$minor}.".($patch + 1),
                'minor' => "Minor — v{$major}.".($minor + 1).'.0',
                'major' => 'Major — v'.($major + 1).'.0.0',
            ],
        );

        return match ($choice) {
            'patch' => "{$major}.{$minor}.".($patch + 1),
            'minor' => "{$major}.".($minor + 1).'.0',
            'major' => ($major + 1).'.0.0',
            default => throw new InvalidArgumentException('Invalid choice'),
        };
    }

    private function updateChangelog(string $version): void
    {
        $path = $this->changelogPath();
        $content = (string) file_get_contents($path);
        $date = Carbon::today()->toDateString();

        if (str_contains($content, '## [Unreleased]')) {
            $content = str_replace(
                '## [Unreleased]',
                "## [{$version}] — {$date}",
                $content
            );
        }

        file_put_contents($path, $content);
    }
}
