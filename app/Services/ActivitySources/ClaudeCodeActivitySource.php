<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClaudeCodeActivitySource implements ActivitySource
{
    public function identifier(): string
    {
        return 'claude-code';
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

        $events = $events->values();

        Log::channel('activity')->info('[activity:scan] claude-code source', ['events_found' => $events->count()]);

        return $events;
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
                $occurredAt = CarbonImmutable::parse($obj['timestamp']);
            } catch (Throwable) {
                continue;
            }

            if ($occurredAt->lessThanOrEqualTo($since)) {
                continue;
            }

            if (($obj['type'] ?? null) === 'system' && ($obj['subtype'] ?? null) === 'local_command') {
                $events->push(new ActivityEventData(
                    type: ActivityEventType::ClaudeSessionStart,
                    sourceType: $this->identifier(),
                    occurredAt: $occurredAt,
                    metadata: [
                        'session_id' => $obj['sessionId'] ?? null,
                        'cwd' => $cwd,
                    ],
                    projectId: $matched->project_id,
                    projectRepositoryId: $matched->id,
                ));

                continue;
            }

            if (($obj['type'] ?? null) === 'assistant') {
                foreach ((array) ($obj['message']['content'] ?? []) as $content) {
                    if (($content['type'] ?? null) !== 'tool_use') {
                        continue;
                    }

                    if (! in_array($content['name'] ?? '', ['Edit', 'Write'], strict: true)) {
                        continue;
                    }

                    $filePath = $content['input']['file_path'] ?? null;

                    $events->push(new ActivityEventData(
                        type: ActivityEventType::ClaudeFileTouch,
                        sourceType: $this->identifier(),
                        occurredAt: $occurredAt,
                        metadata: [
                            'tool' => $content['name'],
                            'file_path' => $filePath,
                        ],
                        projectId: $matched->project_id,
                        projectRepositoryId: $matched->id,
                        filePath: $filePath,
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
        $path = config('activity.claude.projects_path', '~/.claude/projects');

        // @TODO: check these commands
        if (str_starts_with((string) $path, '~')) {
            $home = $_SERVER['HOME'] ?? posix_getpwuid(posix_getuid())['dir'];
            $path = $home.mb_substr((string) $path, 1);
        }

        return $path;
    }
}
