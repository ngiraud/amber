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

class GitActivitySource implements ActivitySource
{
    public function __construct(private readonly ActivitySourceSettings $settings) {}

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
    public function scan(CarbonImmutable $since, CarbonImmutable $until, Collection $repos): Collection
    {
        return $repos
            ->flatMap(fn (ProjectRepository $repo) => $this->scanRepository($repo, $since, $until))
            ->values();
    }

    /**
     * @return Collection<int, ActivityEventData>
     */
    protected function scanRepository(ProjectRepository $repo, CarbonImmutable $since, CarbonImmutable $until): Collection
    {
        $authorEmails = $this->resolveAuthorEmails();

        $commits = $this->scanCommits($repo, $since, $until, $authorEmails);
        $branchSwitches = $this->scanBranchSwitches($repo, $since, $until, $authorEmails);

        return $commits->merge($branchSwitches)->values();
    }

    /**
     * @param  array<int, string>  $authorEmails
     * @return Collection<int, ActivityEventData>
     */
    protected function scanCommits(ProjectRepository $repo, CarbonImmutable $since, CarbonImmutable $until, array $authorEmails): Collection
    {
        // Use a unique separator to split commit blocks from --numstat output
        $separator = '---COMMIT---';

        $result = Process::run([
            'git',
            '-C', $repo->local_path,
            'log',
            '--format='.$separator.'%H|%ae|%aI|%s',
            '--numstat',
            '--after='.$since->toIso8601String(),
            '--before='.$until->toIso8601String(),
        ]);

        if ($result->failed()) {
            return collect();
        }

        $currentBranch = $this->resolveCurrentBranch($repo);

        return collect(explode($separator, mb_trim($result->output())))
            ->filter()
            ->map(fn (string $block) => $this->parseCommitBlock($block))
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
                    'branch' => $currentBranch,
                    'added_lines' => $data['added_lines'],
                    'removed_lines' => $data['removed_lines'],
                    'changed_files' => $data['changed_files'],
                ],
            ))
            ->values();
    }

    /**
     * @param  array<int, string>  $authorEmails
     * @return Collection<int, ActivityEventData>
     */
    protected function scanBranchSwitches(ProjectRepository $repo, CarbonImmutable $since, CarbonImmutable $until, array $authorEmails): Collection
    {
        $result = Process::run([
            'git',
            '-C', $repo->local_path,
            'reflog',
            '--format=%ae|%gI|%gs',
            '--after='.$since->toIso8601String(),
            '--before='.$until->toIso8601String(),
        ]);

        if ($result->failed()) {
            return collect();
        }

        return collect(explode("\n", mb_trim($result->output())))
            ->filter()
            ->map(fn (string $line) => $this->parseReflogLine($line))
            ->filter()
            ->when(! empty($authorEmails), fn (Collection $events) => $events->filter(
                fn (array $data) => ! empty($data['author_email']) && in_array($data['author_email'], $authorEmails)
            ))
            ->map(fn (array $data) => new ActivityEventData(
                sourceType: $this->identifier(),
                type: ActivityEventType::GitBranchSwitch,
                occurredAt: $data['occurred_at'],
                projectRepository: $repo,
                metadata: [
                    'from_branch' => $data['from_branch'],
                    'to_branch' => $data['to_branch'],
                    'author_email' => $data['author_email'],
                ],
            ))
            ->values();
    }

    /**
     * @return array{hash: string, author_email: string, occurred_at: CarbonImmutable, message: string, added_lines: int, removed_lines: int, changed_files: int}|null
     */
    protected function parseCommitBlock(string $block): ?array
    {
        $lines = array_filter(explode("\n", mb_trim($block)));
        $lines = array_values($lines);

        if (empty($lines)) {
            return null;
        }

        // First line is the commit header
        $parts = explode('|', $lines[0], 4);

        if (count($parts) < 4) {
            return null;
        }

        [$hash, $authorEmail, $date, $message] = $parts;

        try {
            $occurredAt = CarbonImmutable::parse($date)->utc();
        } catch (Throwable) {
            return null;
        }

        // Remaining lines are numstat output: added\tremoved\tfile_path
        $addedLines = 0;
        $removedLines = 0;
        $changedFiles = 0;

        foreach (array_slice($lines, 1) as $numstatLine) {
            $columns = explode("\t", $numstatLine);

            if (count($columns) < 3) {
                continue;
            }

            // Binary files show '-' instead of numbers
            $addedLines += is_numeric($columns[0]) ? (int) $columns[0] : 0;
            $removedLines += is_numeric($columns[1]) ? (int) $columns[1] : 0;
            $changedFiles++;
        }

        return [
            'hash' => $hash,
            'author_email' => $authorEmail,
            'occurred_at' => $occurredAt,
            'message' => $message,
            'added_lines' => $addedLines,
            'removed_lines' => $removedLines,
            'changed_files' => $changedFiles,
        ];
    }

    /**
     * @return array{author_email: string, occurred_at: CarbonImmutable, from_branch: string, to_branch: string}|null
     */
    protected function parseReflogLine(string $line): ?array
    {
        $parts = explode('|', $line, 3);

        if (count($parts) < 3) {
            return null;
        }

        [$authorEmail, $date, $subject] = $parts;

        // Match "checkout: moving from {from} to {to}"
        if (! preg_match('/^checkout: moving from (.+) to (.+)$/', mb_trim($subject), $matches)) {
            return null;
        }

        try {
            $occurredAt = CarbonImmutable::parse($date)->utc();
        } catch (Throwable) {
            return null;
        }

        return [
            'author_email' => $authorEmail,
            'occurred_at' => $occurredAt,
            'from_branch' => $matches[1],
            'to_branch' => $matches[2],
        ];
    }

    protected function resolveCurrentBranch(ProjectRepository $repo): ?string
    {
        $result = Process::run(['git', '-C', $repo->local_path, 'rev-parse', '--abbrev-ref', 'HEAD']);

        if ($result->failed()) {
            return null;
        }

        $branch = mb_trim($result->output());

        return $branch !== '' && $branch !== 'HEAD' ? $branch : null;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveAuthorEmails(): array
    {
        return array_filter(array_map('trim', $this->settings->git->author_emails));
    }
}
