<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Enums\SessionReconstructMode;
use App\Enums\SessionSource;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReconstructDailySessions extends Action
{
    public function __construct(
        private readonly CreateSession $createSession,
        private readonly ActivitySettings $settings,
    ) {}

    /**
     * @return Collection<int, Session>
     */
    public function handle(CarbonImmutable $date, ?Project $project = null, SessionReconstructMode $mode = SessionReconstructMode::Gaps): Collection
    {
        if ($mode === SessionReconstructMode::Replace) {
            $this->clearAutoSessions($date, $project);
        }

        $events = ActivityEvent::query()
            ->whereDate('occurred_at', $date)
            ->whereNotNull('project_id')
            ->orderBy('occurred_at')
            ->when($project !== null, fn (Builder $q) => $q->where('project_id', $project->id))
            ->get();

        $projectIds = $events->pluck('project_id')->unique()->values();

        $projects = match (is_null($project)) {
            true => Project::whereIn('id', $projectIds)->get(),
            false => EloquentCollection::wrap([$project]),
        };

        $projects = $projects->keyBy('id');

        $existingSessions = Session::query()
            ->whereIn('project_id', $projectIds)
            ->where('started_at', '<', $date->endOfDay())
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $date->startOfDay()))
            ->get()
            ->groupBy('project_id');

        $generated = collect();

        foreach ($events->groupBy('project_id') as $projectId => $projectEvents) {
            $currentProject = $projects->get($projectId);

            if ($currentProject === null) {
                continue;
            }

            $blocks = $this->detectBlocks($projectEvents);

            $projectSessions = $existingSessions->get($projectId) ?? collect();

            foreach ($blocks as [$blockStart, $blockEnd]) {
                $overlaps = $projectSessions->contains(
                    fn (Session $session): bool => $session->started_at < $blockEnd && ($session->ended_at === null || $session->ended_at > $blockStart)
                );

                if ($overlaps) {
                    continue;
                }

                $session = $this->createSession
                    ->auto()
                    ->handle($currentProject, new SessionData(startedAt: $blockStart, endedAt: $blockEnd));

                ActivityEvent::query()
                    ->where('project_id', $currentProject->id)
                    ->whereBetween('occurred_at', [$blockStart, $blockEnd])
                    ->whereNull('session_id')
                    ->update(['session_id' => $session->id]);

                $generated->push($session);
            }
        }

        return $generated;
    }

    private function clearAutoSessions(CarbonImmutable $date, ?Project $project): void
    {
        $sessionIds = Session::query()
            ->where('source', SessionSource::Auto)
            ->whereDate('started_at', $date)
            ->when($project !== null, fn ($q) => $q->where('project_id', $project->id))
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return;
        }

        ActivityEvent::query()
            ->whereIn('session_id', $sessionIds)
            ->update(['session_id' => null]);

        Session::query()->whereIn('id', $sessionIds)->delete();
    }

    /**
     * Split a sorted collection of events into contiguous work blocks using gap analysis.
     *
     * @param  Collection<int, ActivityEvent>  $events
     * @return array<int, array{CarbonImmutable, CarbonImmutable}>
     */
    private function detectBlocks(Collection $events): array
    {
        $blocks = [];
        $blockStart = null;
        $lastTime = null;

        foreach ($events as $event) {
            $time = CarbonImmutable::instance($event->occurred_at);

            if ($blockStart === null) {
                $blockStart = $time;
                $lastTime = $time;

                continue;
            }

            $gap = (int) $lastTime->diffInMinutes($time);

            if ($gap > $this->settings->idle_timeout_minutes) {
                $blocks[] = [$blockStart, $lastTime->addMinutes($this->settings->block_end_padding_minutes)];
                $blockStart = $time;
            }

            $lastTime = $time;
        }

        if ($blockStart !== null) {
            $blocks[] = [$blockStart, $lastTime->addMinutes($this->settings->block_end_padding_minutes)];
        }

        return $blocks;
    }
}
