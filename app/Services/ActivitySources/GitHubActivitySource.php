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

class GitHubActivitySource implements ActivitySource
{
    public function identifier(): ActivityEventSourceType
    {
        return ActivityEventSourceType::GitHub;
    }

    public function isAvailable(): bool
    {
        return Process::run(['gh', 'auth', 'status'])->successful();
    }

    /**
     * @param  Collection<int, ProjectRepository>  $repos
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, Collection $repos): Collection
    {
        $username = $this->resolveUsername();

        return $repos
            ->flatMap(fn (ProjectRepository $repo) => $this->scanRepository($repo, $since, $username))
            ->values();
    }

    /**
     * @return Collection<int, ActivityEventData>
     */
    protected function scanRepository(ProjectRepository $repo, CarbonImmutable $since, ?string $username): Collection
    {
        $githubRepo = $this->detectGitHubRepo($repo);

        if ($githubRepo === null) {
            return collect();
        }

        $args = [
            'gh', 'pr', 'list',
            '--state', 'all',
            '--json', 'number,title,body,state,createdAt,mergedAt,url,headRefName',
            '--repo', $githubRepo,
            '--limit', '100',
        ];

        if ($username !== null) {
            array_push($args, '--author', $username);
        }

        $result = Process::run($args);

        if ($result->failed()) {
            return collect();
        }

        $prs = json_decode($result->output(), true);

        if (! is_array($prs)) {
            return collect();
        }

        $events = collect();

        foreach ($prs as $pr) {
            $events = $events->merge($this->buildPrEvents($pr, $repo, $githubRepo, $since));
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $pr
     * @return Collection<int, ActivityEventData>
     */
    protected function buildPrEvents(array $pr, ProjectRepository $repo, string $githubRepo, CarbonImmutable $since): Collection
    {
        $events = collect();

        $baseMeta = [
            'number' => $pr['number'],
            'title' => $pr['title'] ?? '',
            'body' => $pr['body'] ?? '',
            'url' => $pr['url'] ?? '',
            'branch' => $pr['headRefName'] ?? '',
            'repo' => $githubRepo,
        ];

        // PR opened event
        if (! empty($pr['createdAt'])) {
            try {
                $createdAt = CarbonImmutable::parse($pr['createdAt'])->utc();

                if ($createdAt->isAfter($since)) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::GitPrOpened,
                        occurredAt: $createdAt,
                        projectRepository: $repo,
                        metadata: $baseMeta,
                    ));
                }
            } catch (Throwable) {
                // Unparseable date — skip
            }
        }

        // PR merged event
        if (! empty($pr['mergedAt'])) {
            try {
                $mergedAt = CarbonImmutable::parse($pr['mergedAt'])->utc();

                if ($mergedAt->isAfter($since)) {
                    $events->push(new ActivityEventData(
                        sourceType: $this->identifier(),
                        type: ActivityEventType::GitPrMerged,
                        occurredAt: $mergedAt,
                        projectRepository: $repo,
                        metadata: array_merge($baseMeta, ['merged_at' => $mergedAt->toIso8601String()]),
                    ));
                }
            } catch (Throwable) {
                // Unparseable date — skip
            }
        }

        return $events;
    }

    protected function detectGitHubRepo(ProjectRepository $repo): ?string
    {
        $result = Process::run(['git', '-C', $repo->local_path, 'remote', 'get-url', 'origin']);

        if ($result->failed()) {
            return null;
        }

        $remoteUrl = mb_trim($result->output());

        // Match SSH (git@github.com:owner/repo.git) and HTTPS (https://github.com/owner/repo.git)
        if (preg_match('#github\.com[:/]([^/]+/[^/]+?)(?:\.git)?$#', $remoteUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function resolveUsername(): ?string
    {
        $username = AppSetting::get('github_username');

        if (! empty($username)) {
            return $username;
        }

        $result = Process::run(['gh', 'api', 'user', '--jq', '.login']);

        if ($result->failed()) {
            return null;
        }

        $login = mb_trim($result->output());

        return $login !== '' ? $login : null;
    }
}
