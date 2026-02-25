<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProjectRepository;
use Native\Desktop\Facades\ChildProcess;

class FileWatcherService
{
    public const string ALIAS = 'file-watcher';

    public static function make(): self
    {
        return app(self::class);
    }

    public function start(): void
    {
        $paths = $this->watchedPaths();

        if (empty($paths)) {
            return;
        }

        $excluded = config('activity.fswatch.excluded_patterns', []);
        $debounce = (int) config('activity.fswatch.debounce_seconds', 3);

        $excludeArgs = array_merge(...array_map(fn (string $p) => ['--exclude', $p], $excluded));
        $args = array_merge(
            ['fswatch', '-r', '--event', 'Updated', '--latency', (string) $debounce, '--timestamp', '--format-time', '%s'],
            $excludeArgs,
            $paths,
        );

        ChildProcess::start(
            cmd: $args,
            alias: self::ALIAS,
            persistent: true,
        );
    }

    public function stop(): void
    {
        ChildProcess::stop(self::ALIAS);

    }

    public function restart(): void
    {
        ChildProcess::restart(self::ALIAS);
    }

    /**
     * @return array<int, string>
     */
    protected function watchedPaths(): array
    {
        return ProjectRepository::query()
            ->forActiveProjects()
            ->pluck('local_path')
            ->filter(fn (string $path) => is_dir($path))
            ->values()
            ->all();
    }
}
