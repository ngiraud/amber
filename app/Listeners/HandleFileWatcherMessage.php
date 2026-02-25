<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Services\FileWatcherService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Native\Desktop\Events\ChildProcess\MessageReceived;

class HandleFileWatcherMessage
{
    /** @var Collection<int, ProjectRepository>|null */
    protected ?Collection $projectRepositories = null;

    public function __construct(protected readonly RecordActivityEvent $recordEvent) {}

    public function handle(MessageReceived $event): void
    {
        if ($event->alias !== FileWatcherService::ALIAS) {
            return;
        }

        $entries = array_values(array_filter(array_map(
            fn (string $line) => $this->parseLine($line),
            explode("\n", mb_trim((string) $event->data)),
        )));

        // deduplicate by file path — keep first occurrence (earliest timestamp)
        $seen = [];
        $entries = array_filter($entries, function (array $entry) use (&$seen): bool {
            [, $filePath] = $entry;

            if (isset($seen[$filePath])) {
                return false;
            }

            return $seen[$filePath] = true;
        });

        if (empty($entries)) {
            return;
        }

        $this->projectRepositories = ProjectRepository::forActiveProjects()->get();

        foreach ($entries as $entry) {
            [$occurredAt, $filePath] = $entry;

            if (! $this->isAllowedExtension($filePath)) {
                continue;
            }

            $projectRepository = $this->getProjectRepositoryFromPath($filePath);

            if (! $projectRepository) {
                continue;
            }

            $this->recordEvent->handle(new ActivityEventData(
                sourceType: ActivityEventSourceType::Fswatch,
                type: ActivityEventType::FileChange,
                occurredAt: $occurredAt,
                projectRepository: $projectRepository,
                metadata: ['file_path' => $filePath],
            ));
        }
    }

    /**
     * @return array{CarbonImmutable, string}|null
     */
    protected function parseLine(string $line): ?array
    {
        $line = mb_trim($line);

        if ($line === '') {
            return null;
        }

        $spacePos = mb_strpos($line, ' ');

        if ($spacePos === false) {
            return null;
        }

        $timestamp = (int) mb_substr($line, 0, $spacePos);
        $filePath = mb_rtrim(mb_substr($line, $spacePos + 1), '~');

        if ($filePath === '' || $timestamp === 0) {
            return null;
        }

        return [CarbonImmutable::createFromTimestamp($timestamp), $filePath];
    }

    protected function isAllowedExtension(string $filePath): bool
    {
        $allowed = config('activity.fswatch.allowed_extensions', []);

        if (empty($allowed)) {
            return true;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return in_array(mb_strtolower($extension), $allowed, strict: true);
    }

    protected function getProjectRepositoryFromPath(string $filePath): ?ProjectRepository
    {
        return $this->projectRepositories
            ->filter(fn (ProjectRepository $repo) => str_starts_with($filePath, $repo->local_path))
            ->sortByDesc(fn (ProjectRepository $repo) => mb_strlen($repo->local_path))
            ->first();
    }
}
