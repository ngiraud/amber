<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Throwable;

class GitActivitySource implements ActivitySource
{
    public function identifier(): string
    {
        return 'git';
    }

    public function isAvailable(): bool
    {
        return Process::run(['git', '--version'])->successful();
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, Collection $repos): Collection
    {
        return $repos
            ->flatMap(fn (ProjectRepository $repo) => $this->scanRepository($repo, $since))
            ->values();
    }

    /**
     * @return Collection<int, ActivityEventData>
     */
    protected function scanRepository(ProjectRepository $repo, CarbonImmutable $since): Collection
    {
        $result = Process::run([
            'git',
            '-C', $repo->local_path,
            'log',
            '--format=%H|%ae|%aI|%s',
            '--after='.$since->toIso8601String(),
        ]);

        if ($result->failed()) {
            return collect();
        }

        $authorEmail = config('activity.git.author_email');

        return collect(explode("\n", mb_trim($result->output())))
            ->filter()
            ->map(fn (string $line) => $this->parseLine($line))
            ->filter()
            ->when(
                $authorEmail !== null,
                fn (Collection $events) => $events->filter(
                    fn (ActivityEventData $data) => ($data->metadata['author_email'] ?? null) === $authorEmail
                )
            )
            ->map(fn (ActivityEventData $data) => new ActivityEventData(
                type: $data->type,
                sourceType: $data->sourceType,
                occurredAt: $data->occurredAt,
                metadata: $data->metadata,
                projectId: $repo->project_id,
                projectRepositoryId: $repo->id,
            ))
            ->values();
    }

    protected function parseLine(string $line): ?ActivityEventData
    {
        $parts = explode('|', $line, 4);

        if (count($parts) < 4) {
            return null;
        }

        [$hash, $authorEmail, $date, $message] = $parts;

        try {
            $occurredAt = CarbonImmutable::parse($date);
        } catch (Throwable) {
            return null;
        }

        return new ActivityEventData(
            type: ActivityEventType::GitCommit,
            sourceType: $this->identifier(),
            occurredAt: $occurredAt,
            metadata: [
                'hash' => $hash,
                'author_email' => $authorEmail,
                'message' => $message,
            ],
        );
    }
}
