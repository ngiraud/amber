<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\DayContext;
use App\Models\ActivityEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class CollectDayContext extends Action
{
    public function handle(Project $project, CarbonImmutable $date): DayContext
    {
        $events = ActivityEvent::query()
            ->where('project_id', $project->id)
            ->whereNotNull('session_id')
            ->whereBetween('occurred_at', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('occurred_at')
            ->get();

        $labels = [];
        $details = [];
        $filesChanged = 0;

        $promptEvents = $events->filter(fn (ActivityEvent $e) => $e->type->isUserPrompt());
        $sampledPromptIds = $this->sampleEventIds($promptEvents, 10);

        foreach ($events as $event) {
            /** @var ActivityEvent $event */
            if ($event->type->isUserPrompt() && ! in_array($event->id, $sampledPromptIds, true)) {
                continue;
            }

            $parts = $event->type->toContextParts($event->metadata);

            if ($parts['label'] !== null) {
                $labels[] = $parts['label'];
            }

            if ($parts['detail'] !== null) {
                $details[] = $parts['detail'];
            }

            if ($event->type->isFileTouch()) {
                $filesChanged++;
            }
        }

        return new DayContext(
            labels: array_values(array_unique($labels)),
            details: $details,
            filesChanged: $filesChanged,
        );
    }

    /**
     * Picks up to $max events evenly distributed across the chronologically ordered collection,
     * so the sample represents the spread of the day rather than clustering at one end.
     *
     * @param  Collection<int, ActivityEvent>  $events
     * @return array<int, string>
     */
    private function sampleEventIds(Collection $events, int $max): array
    {
        if ($events->count() <= $max) {
            return $events->pluck('id')->all();
        }

        $indices = array_map(
            fn (int $i) => (int) round($i * ($events->count() - 1) / ($max - 1)),
            range(0, $max - 1),
        );

        return $events->values()->only($indices)->pluck('id')->all();
    }
}
