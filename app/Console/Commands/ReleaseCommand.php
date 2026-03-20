<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

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

        // 1. Preflight checks
        title('Preflight Checks');

        $branch = trim((string) shell_exec('git rev-parse --abbrev-ref HEAD'));

        $passed = true;

        task('On main branch', function () use ($branch): bool {
            return $branch === 'main';
        });

        if ($branch !== 'main') {
            error("Current branch: {$branch}");
            $passed = false;
        }

        task('Working tree clean', function (): bool {
            return trim((string) shell_exec('git status --porcelain')) === '';
        });

        if (trim((string) shell_exec('git status --porcelain')) !== '') {
            error('Uncommitted changes detected. Commit or stash them first.');
            $passed = false;
        }

        task('Up to date with origin', function (): bool {
            shell_exec('git fetch origin --quiet 2>&1');

            return (int) trim((string) shell_exec('git rev-list HEAD..origin/main --count')) === 0;
        });

        $behind = (int) trim((string) shell_exec('git rev-list HEAD..origin/main --count'));
        if ($behind > 0) {
            error("Branch is {$behind} commit(s) behind origin/main. Run git pull first.");
            $passed = false;
        }

        if (! $passed) {
            return self::FAILURE;
        }

        // 2. Current state
        $currentVersion = $this->currentVersion();

        title('Current State');
        info("Current version: v{$currentVersion}");

        $log = shell_exec('git log $(git describe --tags --abbrev=0 2>/dev/null || git rev-list --max-parents=0 HEAD)..HEAD --pretty=format:"%h|%s|%cr" 2>/dev/null');
        $commits = array_filter(array_map(
            fn (string $line): array => explode('|', $line, 3),
            explode("\n", trim((string) $log))
        ));

        if ($commits !== []) {
            table(['Hash', 'Message', 'When'], $commits);
        } else {
            warning('No commits since last tag.');
        }

        // 3. Tests
        title('Test Suite');
        info('Running composer test:all...');

        passthru('composer test:all 2>&1', $exitCode);

        if ($exitCode !== 0) {
            error('Tests failed. Fix issues before releasing.');

            return self::FAILURE;
        }

        info('All checks passed.');

        // 4. Select version
        title('Version');
        $newVersion = $this->selectVersion($currentVersion);

        if (! confirm("Create and push tag v{$newVersion}?")) {
            warning('Release cancelled.');

            return self::SUCCESS;
        }

        // 5. Changelog + tag
        title('Release');

        task('Update CHANGELOG.md', function () use ($newVersion): bool {
            $this->updateChangelog($newVersion);

            return true;
        });

        task("Commit, tag v{$newVersion}, and push", function () use ($newVersion): bool {
            shell_exec('git add CHANGELOG.md');
            shell_exec("git commit -m \"chore: release v{$newVersion} [skip ci]\"");
            shell_exec('git push origin main --quiet');
            shell_exec("git tag v{$newVersion}");
            exec("git push origin v{$newVersion} --quiet", result_code: $code);

            return $code === 0;
        });

        outro("v{$newVersion} released. GitHub Actions is building the artifacts.");

        return self::SUCCESS;
    }

    private function currentVersion(): string
    {
        $changelog = (string) file_get_contents(base_path('CHANGELOG.md'));

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
                'major' => "Major — v".($major + 1).'.0.0',
            ],
        );

        return match ($choice) {
            'patch' => "{$major}.{$minor}.".($patch + 1),
            'minor' => "{$major}.".($minor + 1).'.0',
            'major' => ($major + 1).'.0.0',
        };
    }

    private function updateChangelog(string $version): void
    {
        $path = base_path('CHANGELOG.md');
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
