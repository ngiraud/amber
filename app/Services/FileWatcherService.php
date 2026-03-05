<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityEventSourceType;
use App\Models\ProjectRepository;
use App\Settings\ActivitySourceSettings;
use Illuminate\Support\Facades\Process;
use Native\Desktop\Facades\ChildProcess;

class FileWatcherService
{
    public const string ALIAS = 'file-watcher';

    public function __construct(private readonly ActivitySourceSettings $settings) {}

    public static function make(): self
    {
        return app(self::class);
    }

    public static function isAvailable(): bool
    {
        return Process::run(['fswatch', '--version'])->successful();
    }

    public function start(): void
    {
        if (! ActivityEventSourceType::Fswatch->isEnabled()) {
            return;
        }

        $paths = $this->watchedPaths();

        if (empty($paths)) {
            return;
        }

        $excludeArgs = array_merge(...array_map(fn (string $p) => ['--exclude', $p], $this->settings->fswatch->excluded_patterns));
        $args = array_merge(
            ['fswatch', '-r', '--event', 'Updated', '--latency', (string) $this->settings->fswatch->debounce_seconds, '--timestamp', '--format-time', '%s'],
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
