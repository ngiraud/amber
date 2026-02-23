<?php

declare(strict_types=1);

namespace App\Services\ActivitySources;

use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\AppSetting;
use App\Models\ProjectRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Throwable;

class GitActivitySource implements ActivitySource
{
    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::Git;
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

        $authorEmails = AppSetting::get('git_author_emails');

        if (empty($authorEmails)) {
            $authorEmails = explode(',', config('activity.git.author_emails', ''));
        }

        return collect(explode("\n", mb_trim($result->output())))
            ->filter()
            ->map(fn (string $line) => $this->parseLine($line))
            ->filter()
            ->when(! empty($authorEmails), fn (Collection $events) => $events->filter(
                fn (array $data) => ! empty($data['author_email']) && in_array($data['author_email'], $authorEmails)
            ))
            ->map(fn (array $data) => new ActivityEventData(
                sourceType: $this->identifier(),
                type: ActivityEventType::GitCommit,
                occurredAt: $data['occurred_at'],
                projectRepository: $repo,
                metadata: [
                    'hash' => $data['hash'],
                    'author_email' => $data['author_email'],
                    'message' => $data['message'],
                ],
            ))
            ->values();
    }

    /**
     * @return array{hash: string, author_email: string, occurred_at: CarbonImmutable, message: string}|null
     */
    protected function parseLine(string $line): ?array
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

        return [
            'hash' => $hash,
            'author_email' => $authorEmail,
            'occurred_at' => $occurredAt,
            'message' => $message,
        ];
    }
}
