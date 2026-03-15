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
use PDO;
use Throwable;

class OpencodeActivitySource implements ActivitySource
{
    use ResolvesHomePath;

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
    public function scan(CarbonImmutable $since, CarbonImmutable $until, Collection $repos): Collection
    {
        $dbPath = $this->dbPath();

        if (! file_exists($dbPath)) {
            return collect();
        }

        try {
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Opencode timestamps are in milliseconds
            $sinceMs = $since->timestamp * 1000;
            $untilMs = $until->timestamp * 1000;

            $stmt = $pdo->prepare('SELECT * FROM session WHERE time_updated > :since AND time_created <= :until');
            $stmt->execute(['since' => $sinceMs, 'until' => $untilMs]);
            $sessions = $stmt->fetchAll();

            $events = collect();

            foreach ($sessions as $session) {
                $localPath = $session['directory'] ?? null;

                if ($localPath === null) {
                    continue;
                }

                $matchedRepo = ProjectRepository::findBestMatchForPath($repos, $localPath);

                if ($matchedRepo === null) {
                    continue;
                }

                $sessionId = $session['id'];

                // Session start event
                $sessionStartedAt = CarbonImmutable::createFromTimestampMs($session['time_created'])->utc();
                if ($sessionStartedAt->greaterThan($since) && $sessionStartedAt->lessThanOrEqualTo($until)) {
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

                // User prompts — text parts linked to user messages
                $promptStmt = $pdo->prepare('
                    SELECT p.time_created, p.data
                    FROM part p
                    JOIN message m ON p.message_id = m.id
                    WHERE p.session_id = :session_id
                      AND json_extract(p.data, \'$.type\') = \'text\'
                      AND json_extract(m.data, \'$.role\') = \'user\'
                      AND p.time_created > :since
                      AND p.time_created <= :until
                ');
                $promptStmt->execute(['session_id' => $sessionId, 'since' => $sinceMs, 'until' => $untilMs]);

                foreach ($promptStmt->fetchAll() as $part) {
                    $data = json_decode($part['data'], true);
                    $text = $data['text'] ?? null;

                    if (! $text) {
                        continue;
                    }

                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::OpencodeUserPrompt,
                        occurredAt: CarbonImmutable::createFromTimestampMs($part['time_created'])->utc(),
                        projectRepository: $matchedRepo,
                        metadata: [
                            'session_id' => $sessionId,
                            'prompt' => mb_substr($text, 0, 500),
                        ],
                    ));
                }

                // File touches — patch parts contain git hash and modified file paths
                $patchStmt = $pdo->prepare('
                    SELECT time_created, data
                    FROM part
                    WHERE session_id = :session_id
                      AND json_extract(data, \'$.type\') = \'patch\'
                      AND time_created > :since
                      AND time_created <= :until
                ');
                $patchStmt->execute(['session_id' => $sessionId, 'since' => $sinceMs, 'until' => $untilMs]);

                foreach ($patchStmt->fetchAll() as $part) {
                    $data = json_decode($part['data'], true);
                    $files = $data['files'] ?? [];
                    $hash = $data['hash'] ?? null;
                    $occurredAt = CarbonImmutable::createFromTimestampMs($part['time_created'])->utc();

                    foreach ($files as $filePath) {
                        $events->push(new ActivityEventData(
                            sourceType: $this->identifier(),
                            type: ActivityEventType::OpencodeFileTouch,
                            occurredAt: $occurredAt,
                            projectRepository: $matchedRepo,
                            metadata: [
                                'session_id' => $sessionId,
                                'file_path' => $filePath,
                                'patch_hash' => $hash,
                            ],
                        ));
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
        return mb_rtrim($this->expandTilde($this->settings->opencode->projects_path), '/').'/opencode.db';
    }
}
