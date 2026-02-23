<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

class ClaudeCodeActivitySource implements ActivitySource
{
    protected string $projectsPath = '';

    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::ClaudeCode;
    }

    public function isAvailable(): bool
    {
        return is_dir($this->projectsPath());
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, Collection $repos): Collection
    {
        $events = collect();

        foreach (glob($this->projectsPath().'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            foreach (glob($dir.'/*.jsonl') ?: [] as $file) {
                $events = $events->merge($this->scanFile($file, $repos, $since));
            }
        }

        return $events->values();
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    protected function scanFile(string $file, Collection $repos, CarbonImmutable $since): Collection
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($lines)) {
            return collect();
        }

        $cwd = $this->resolveCwd($lines);

        if ($cwd === null) {
            return collect();
        }

        $matched = $repos
            ->filter(fn (ProjectRepository $repo) => str_starts_with($cwd, $repo->local_path))
            ->sortByDesc(fn (ProjectRepository $repo) => mb_strlen($repo->local_path))
            ->first();

        if ($matched === null) {
            return collect();
        }

        $events = collect();

        foreach ($lines as $line) {
            $obj = json_decode($line, true);

            if (! isset($obj['timestamp'])) {
                continue;
            }

            try {
                $occurredAt = CarbonImmutable::parse($obj['timestamp'])->utc();
            } catch (Throwable) {
                continue;
            }

            if ($occurredAt->lessThanOrEqualTo($since)) {
                continue;
            }

            // Session start
            if (($obj['type'] ?? null) === 'system' && ($obj['subtype'] ?? null) === 'local_command') {
                $events->push(new ActivityEventData(
                    sourceType: $this->identifier(),
                    type: ActivityEventType::ClaudeSessionStart,
                    occurredAt: $occurredAt,
                    projectRepository: $matched,
                    metadata: [
                        'session_id' => $obj['sessionId'] ?? null,
                        'cwd' => $cwd,
                    ],
                ));

                continue;
            }

            // File touch
            if (($obj['type'] ?? null) === 'assistant') {
                foreach ((array) ($obj['message']['content'] ?? []) as $content) {
                    if (($content['type'] ?? null) !== 'tool_use') {
                        continue;
                    }

                    if (! in_array($content['name'] ?? '', ['Edit', 'Write'], strict: true)) {
                        continue;
                    }

                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::ClaudeFileTouch,
                        occurredAt: $occurredAt,
                        projectRepository: $matched,
                        metadata: [
                            'tool' => $content['name'],
                            'file_path' => $content['input']['file_path'] ?? null,
                        ],
                    ));
                }
            }
        }

        return $events;
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function resolveCwd(array $lines): ?string
    {
        foreach ($lines as $line) {
            $obj = json_decode($line, true);

            if (! empty($obj['cwd'])) {
                return $obj['cwd'];
            }
        }

        return null;
    }

    protected function projectsPath(): string
    {
        if (! empty($this->projectsPath)) {
            return $this->projectsPath;
        }

        $path = config('activity.claude.projects_path', '');

        // @TODO: check these commands
        if (str_starts_with((string) $path, '~')) {
            $home = $_SERVER['HOME'] ?? posix_getpwuid(posix_getuid())['dir'];
            $path = $home.mb_substr((string) $path, 1);
        }

        $this->projectsPath = $path;

        return $this->projectsPath;
    }
}
