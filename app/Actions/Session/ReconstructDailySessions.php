<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReconstructDailySessions extends Action
{
    /** Gap above which we consider activity belongs to a new work block (in minutes). */
    private const int IDLE_THRESHOLD_MINUTES = 30;

    /** Padding added after the last event in a block (in minutes). */
    private const int BLOCK_END_PADDING_MINUTES = 15;

    public function __construct(private readonly CreateSession $createSession) {}

    /**
     * @return Collection<int, Session>
     */
    public function handle(CarbonImmutable $date, ?Project $project = null): Collection
    {
        $eventsQuery = ActivityEvent::query()
            ->whereDate('occurred_at', $date)
            ->whereNotNull('project_id')
            ->orderBy('occurred_at');

        if ($project !== null) {
            $eventsQuery->where('project_id', $project->id);
        }

        $events = $eventsQuery->get();

        $generated = collect();

        foreach ($events->groupBy('project_id') as $projectId => $projectEvents) {
            $proj = Project::find($projectId);

            if ($proj === null) {
                continue;
            }

            $blocks = $this->detectBlocks($projectEvents);

            foreach ($blocks as [$blockStart, $blockEnd]) {
                $overlaps = Session::query()
                    ->where('project_id', $projectId)
                    ->where('started_at', '<', $blockEnd)
                    ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $blockStart))
                    ->exists();

                if ($overlaps) {
                    continue;
                }

                $session = $this->createSession
                    ->reconstructed()
                    ->handle($proj, new SessionData(startedAt: $blockStart, endedAt: $blockEnd));

                $generated->push($session);
            }
        }

        return $generated;
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

            if ($gap > self::IDLE_THRESHOLD_MINUTES) {
                $blocks[] = [$blockStart, $lastTime->addMinutes(self::BLOCK_END_PADDING_MINUTES)];
                $blockStart = $time;
            }

            $lastTime = $time;
        }

        if ($blockStart !== null && $lastTime !== null) {
            $blocks[] = [$blockStart, $lastTime->addMinutes(self::BLOCK_END_PADDING_MINUTES)];
        }

        return $blocks;
    }
}
