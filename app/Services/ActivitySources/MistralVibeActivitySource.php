<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\ActivitySources\Concerns\ResolvesHomePath;
use App\Services\ActivitySources\Contracts\ActivitySource;
use App\Settings\ActivitySourceSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Throwable;

class MistralVibeActivitySource implements ActivitySource
{
    use ResolvesHomePath;

    public function __construct(private readonly ActivitySourceSettings $settings) {}

    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::MistralVibe;
    }

    public function isAvailable(): bool
    {
        return Process::run(['vibe', '--version'])->successful();
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, CarbonImmutable $until, Collection $repos): Collection
    {
        $events = collect();
        $projectsPath = $this->projectsPath();

        foreach (glob($projectsPath.'/*', GLOB_ONLYDIR) ?: [] as $sessionDir) {
            $metaFile = $sessionDir.'/meta.json';
            $messagesFile = $sessionDir.'/messages.jsonl';

            if (! file_exists($metaFile) || ! file_exists($messagesFile)) {
                continue;
            }

            if (filemtime($metaFile) < $since->timestamp) {
                continue;
            }

            $meta = json_decode(file_get_contents($metaFile), true);
            $localPath = $meta['environment']['working_directory'] ?? null;

            if ($localPath === null) {
                continue;
            }

            $matchedRepo = ProjectRepository::findBestMatchForPath($repos, $localPath);

            if ($matchedRepo === null) {
                continue;
            }

            $events = $events->merge($this->scanSession($sessionDir, $meta, $matchedRepo, $since, $until));
        }

        return $events->values();
    }

    protected function scanSession(string $dir, array $meta, ProjectRepository $repo, CarbonImmutable $since, CarbonImmutable $until): Collection
    {
        try {
            $occurredAt = CarbonImmutable::parse($meta['start_time'])->utc();
        } catch (Throwable) {
            return collect();
        }

        if ($occurredAt->lessThanOrEqualTo($since) || $occurredAt->greaterThan($until)) {
            // We still scan if the file was modified recently, but we filter individual events.
            // Actually Vibe logs don't have individual timestamps.
            // If the session started before $since, we might still want to check if new messages were added?
            // But without timestamps we can't tell which ones are new.
            // For now, only scan if the session started after $since.
            return collect();
        }

        $events = collect();
        $sessionId = $meta['session_id'] ?? basename($dir);

        // Session start
        $events->push(new ActivityEventData(
            sourceType: $this->identifier(),
            type: ActivityEventType::VibeSessionStart,
            occurredAt: $occurredAt,
            projectRepository: $repo,
            metadata: [
                'session_id' => $sessionId,
                'git_branch' => $meta['git_branch'] ?? null,
                'git_commit' => $meta['git_commit'] ?? null,
                'source_file' => $dir.'/meta.json',
            ],
        ));

        // Session end event
        $endTime = $meta['end_time'] ?? null;
        if ($endTime !== null) {
            try {
                $endedAt = CarbonImmutable::parse($endTime)->utc();
                if ($endedAt->greaterThan($since) && $endedAt->lessThanOrEqualTo($until)) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::VibeSessionEnd,
                        occurredAt: $endedAt,
                        projectRepository: $repo,
                        metadata: [
                            'session_id' => $sessionId,
                            'source_file' => $dir.'/meta.json',
                        ],
                    ));
                }
            } catch (Throwable) {
                // ignore invalid end_time
            }
        }

        $messagesFile = $dir.'/messages.jsonl';
        $lines = file($messagesFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $msg = json_decode($line, true);
            if (! is_array($msg)) {
                continue;
            }

            // User prompt
            if (($msg['role'] ?? null) === 'user') {
                $events->push(new ActivityEventData(
                    sourceType: $this->identifier(),
                    type: ActivityEventType::VibeUserPrompt,
                    occurredAt: $occurredAt,
                    projectRepository: $repo,
                    metadata: [
                        'session_id' => $sessionId,
                        'prompt' => mb_substr((string) ($msg['content'] ?? ''), 0, 500),
                        'source_file' => $messagesFile,
                    ],
                ));
            }

            // Tool calls (File touch)
            if (($msg['role'] ?? null) === 'assistant' && ! empty($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $toolCall) {
                    $tool = $toolCall['function'] ?? [];
                    $name = $tool['name'] ?? '';
                    if (in_array($name, ['search_replace'], true)) {
                        $args = json_decode($tool['arguments'] ?? '{}', true);
                        $filePath = $args['file_path'] ?? $args['path'] ?? null;
                        if ($filePath) {
                            $events->push(new ActivityEventData(
                                sourceType: $this->identifier(),
                                type: ActivityEventType::VibeFileTouch,
                                occurredAt: $occurredAt,
                                projectRepository: $repo,
                                metadata: [
                                    'tool' => $name,
                                    'file_path' => $filePath,
                                    'source_file' => $messagesFile,
                                ],
                            ));
                        }
                    }
                }
            }
        }

        return $events;
    }

    protected function projectsPath(): string
    {
        return $this->expandTilde($this->settings->mistral_vibe->projects_path);
    }
}
