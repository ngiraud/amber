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
use PDO;
use Throwable;

class OpencodeActivitySource implements ActivitySource
{
    public function __construct(private readonly ActivitySourceSettings $settings) {}

    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::Opencode;
    }

    public function isAvailable(): bool
    {
        $db = $this->dbPath();

        return file_exists($db);
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, Collection $repos): Collection
    {
        $dbPath = $this->dbPath();

        if (! file_exists($dbPath)) {
            return collect();
        }

        try {
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Get sessions modified since $since
            // Opencode timestamps are in milliseconds
            $sinceMs = $since->timestamp * 1000;

            $stmt = $pdo->prepare('SELECT * FROM session WHERE time_updated > :since');
            $stmt->execute(['since' => $sinceMs]);
            $sessions = $stmt->fetchAll();

            $events = collect();

            foreach ($sessions as $session) {
                $localPath = $session['directory'] ?? null;

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

                $sessionId = $session['id'];

                // Session start event
                $sessionStartedAt = CarbonImmutable::createFromTimestampMs($session['time_created'])->utc();
                if ($sessionStartedAt->greaterThan($since)) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::OpencodeSessionStart,
                        occurredAt: $sessionStartedAt,
                        projectRepository: $matchedRepo,
                        metadata: [
                            'session_id' => $sessionId,
                            'title' => $session['title'] ?? null,
                        ],
                    ));
                }

                // Get messages for this session
                $msgStmt = $pdo->prepare('SELECT * FROM message WHERE session_id = :session_id AND time_created > :since');
                $msgStmt->execute(['session_id' => $sessionId, 'since' => $sinceMs]);
                $messages = $msgStmt->fetchAll();

                foreach ($messages as $message) {
                    $data = json_decode($message['data'], true);
                    if (! is_array($data)) {
                        continue;
                    }

                    $occurredAt = CarbonImmutable::createFromTimestampMs($message['time_created'])->utc();

                    // User prompt
                    if (($data['role'] ?? null) === 'user') {
                        $prompt = $data['content'] ?? null;
                        if ($prompt) {
                            $events->push(new ActivityEventData(
                                sourceType: $this->identifier(),
                                type: ActivityEventType::OpencodeUserPrompt,
                                occurredAt: $occurredAt,
                                projectRepository: $matchedRepo,
                                metadata: [
                                    'session_id' => $sessionId,
                                    'prompt' => mb_substr((string) $prompt, 0, 500),
                                ],
                            ));
                        }
                    }

                    // File changes (diffs in Opencode are often in the message data)
                    $diffs = $data['summary']['diffs'] ?? [];
                    if (! empty($diffs)) {
                        foreach ($diffs as $diff) {
                            $events->push(new ActivityEventData(
                                sourceType: $this->identifier(),
                                type: ActivityEventType::OpencodeFileTouch,
                                occurredAt: $occurredAt,
                                projectRepository: $matchedRepo,
                                metadata: [
                                    'file_path' => $diff['file'] ?? null,
                                    'status' => $diff['status'] ?? null,
                                    'additions' => $diff['additions'] ?? 0,
                                    'deletions' => $diff['deletions'] ?? 0,
                                ],
                            ));
                        }
                    }
                }
            }

            return $events->values();
        } catch (Throwable) {
            return collect();
        }
    }

    protected function dbPath(): string
    {
        $path = $this->settings->opencode->projects_path;

        if (str_starts_with($path, '~')) {
            $home = $_SERVER['HOME'] ?? getenv('HOME');
            $path = $home.mb_substr($path, 1);
        }

        return mb_rtrim($path, '/').'/opencode.db';
    }
}
