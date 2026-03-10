<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\Contracts\ActivitySource;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Throwable;

class ClaudeCodeActivitySource implements ActivitySource
{
    public function __construct(private readonly ActivitySourceSettings $settings) {}

    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::ClaudeCode;
    }

    public function isAvailable(): bool
    {
        return Process::run(['claude', '--version'])->successful();
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
                if (filemtime($file) < $since->timestamp) {
                    continue;
                }

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

        foreach (array_reverse($lines) as $line) {
            $obj = json_decode($line, true);

            if (! is_array($obj) || ! isset($obj['timestamp']) || ($obj['isSidechain'] ?? false) === true) {
                continue;
            }

            try {
                $occurredAt = CarbonImmutable::parse($obj['timestamp'])->utc();
            } catch (Throwable) {
                continue;
            }

            if ($occurredAt->lessThanOrEqualTo($since)) {
                break;
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
                        'source_file' => $file,
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
                            'source_file' => $file,
                        ],
                    ));
                }
            }

            // User prompt
            if (($obj['type'] ?? null) === 'user') {
                $prompt = $this->extractPromptText($obj['message']['content'] ?? null);

                if ($prompt !== null) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::ClaudeUserPrompt,
                        occurredAt: $occurredAt,
                        projectRepository: $matched,
                        metadata: [
                            'session_id' => $obj['sessionId'] ?? null,
                            'prompt' => mb_substr($prompt, 0, 500),
                            'source_file' => $file,
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
        foreach (array_slice($lines, 0, 10) as $line) {
            $obj = json_decode($line, true);

            if (is_array($obj) && ! empty($obj['cwd'])) {
                return $obj['cwd'];
            }
        }

        return null;
    }

    protected function extractPromptText(mixed $content): ?string
    {
        if (is_string($content) && $content !== '') {
            return $content;
        }

        if (is_array($content)) {
            foreach ($content as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'text' && ! empty($block['text'])) {
                    return (string) $block['text'];
                }
            }
        }

        return null;
    }

    protected function projectsPath(): string
    {
        $path = $this->settings->claude_code->projects_path;

        if (str_starts_with($path, '~')) {
            $home = $_SERVER['HOME'] ?? getenv('HOME');
            $path = $home.mb_substr($path, 1);
        }

        return $path;
    }
}
