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

class GeminiActivitySource implements ActivitySource
{
    public function __construct(private readonly ActivitySourceSettings $settings) {}

    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::Gemini;
    }

    public function isAvailable(): bool
    {
        return Process::run(['gemini', '--version'])->successful();
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, CarbonImmutable $until, Collection $repos): Collection
    {
        $events = collect();
        $projectsPath = $this->projectsPath();

        foreach (glob($projectsPath.'/*', GLOB_ONLYDIR) ?: [] as $projectDir) {
            $projectName = basename($projectDir);
            $localPath = $this->resolveLocalPath($projectName);

            if ($localPath === null) {
                continue;
            }

            $matchedRepo = $repos
                ->filter(fn (ProjectRepository $repo) => str_starts_with($localPath, $repo->local_path))
                ->sortByDesc(fn (ProjectRepository $repo) => mb_strlen($repo->local_path))
                ->first();

            if ($matchedRepo === null) {
                continue;
            }

            foreach (glob($projectDir.'/chats/*.json') ?: [] as $file) {
                if (filemtime($file) < $since->timestamp) {
                    continue;
                }

                $events = $events->merge($this->scanFile($file, $matchedRepo, $since, $until));
            }
        }

        return $events->values();
    }

    protected function scanFile(string $file, ProjectRepository $repo, CarbonImmutable $since, CarbonImmutable $until): Collection
    {
        $content = file_get_contents($file);
        if (! $content) {
            return collect();
        }

        $data = json_decode($content, true);
        if (! is_array($data) || empty($data['messages'])) {
            return collect();
        }

        $events = collect();
        $sessionId = $data['sessionId'] ?? basename($file, '.json');

        foreach ($data['messages'] as $index => $msg) {
            try {
                $occurredAt = CarbonImmutable::parse($msg['timestamp'])->utc();
            } catch (Throwable) {
                continue;
            }

            if ($occurredAt->lessThanOrEqualTo($since) || $occurredAt->greaterThan($until)) {
                continue;
            }

            // Session start (first message)
            if ($index === 0) {
                $events->push(new ActivityEventData(
                    sourceType: $this->identifier(),
                    type: ActivityEventType::GeminiSessionStart,
                    occurredAt: $occurredAt,
                    projectRepository: $repo,
                    metadata: [
                        'session_id' => $sessionId,
                        'source_file' => $file,
                    ],
                ));
            }

            // User prompt
            if (($msg['type'] ?? null) === 'user') {
                $prompt = $this->extractText($msg['content'] ?? null);
                if ($prompt) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::GeminiUserPrompt,
                        occurredAt: $occurredAt,
                        projectRepository: $repo,
                        metadata: [
                            'session_id' => $sessionId,
                            'prompt' => mb_substr($prompt, 0, 500),
                            'source_file' => $file,
                        ],
                    ));
                }
            }

            // Tool calls (File touch)
            if (! empty($msg['toolCalls'])) {
                foreach ($msg['toolCalls'] as $toolCall) {
                    $toolName = $toolCall['name'] ?? '';
                    if (in_array($toolName, ['replace', 'write_file', 'search_replace'], true)) {
                        $filePath = $toolCall['args']['file_path'] ?? $toolCall['args']['path'] ?? null;
                        if ($filePath) {
                            $events->push(new ActivityEventData(
                                sourceType: $this->identifier(),
                                type: ActivityEventType::GeminiFileTouch,
                                occurredAt: $occurredAt,
                                projectRepository: $repo,
                                metadata: [
                                    'tool' => $toolName,
                                    'file_path' => $filePath,
                                    'source_file' => $file,
                                ],
                            ));
                        }
                    }
                }
            }
        }

        return $events;
    }

    protected function extractText(mixed $content): ?string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $text = '';
            foreach ($content as $block) {
                if (is_array($block) && ! empty($block['text'])) {
                    $text .= $block['text'].' ';
                } elseif (is_string($block)) {
                    $text .= $block.' ';
                }
            }

            return mb_trim($text) ?: null;
        }

        return null;
    }

    protected function resolveLocalPath(string $projectName): ?string
    {
        $projectsFile = dirname($this->projectsPath()).'/projects.json';

        if (! file_exists($projectsFile)) {
            $projectsFile = $this->homePath().'/.gemini/projects.json';
        }

        if (! file_exists($projectsFile)) {
            return null;
        }

        $projects = json_decode(file_get_contents($projectsFile), true)['projects'] ?? [];
        foreach ($projects as $path => $name) {
            if ($name === $projectName) {
                return $path;
            }
        }

        return null;
    }

    protected function projectsPath(): string
    {
        $path = $this->settings->gemini->projects_path;

        if (str_starts_with($path, '~')) {
            $path = $this->homePath().mb_substr($path, 1);
        }

        return $path;
    }

    protected function homePath(): string
    {
        return $_SERVER['HOME'] ?? getenv('HOME');
    }
}
