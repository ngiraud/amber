<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\DayContext;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;

class CollectDayContext extends Action
{
    public function handle(Project $project, CarbonImmutable $date): DayContext
    {
        $events = ActivityEvent::query()
            ->where('project_id', $project->id)
            ->whereNotNull('session_id')
            ->whereBetween('occurred_at', [$date->startOfDay(), $date->endOfDay()])
            ->get();

        $labels = [];
        $details = [];
        $filesChanged = 0;

        foreach ($events as $event) {
            /** @var ActivityEvent $event */
            $parts = $event->type->toContextParts($event->metadata);

            if ($parts['label'] !== null) {
                $labels[] = $parts['label'];
            }

            if ($parts['detail'] !== null) {
                $details[] = $parts['detail'];
            }

            if (in_array($event->type, [ActivityEventType::FileChange, ActivityEventType::ClaudeFileTouch], true)) {
                $filesChanged++;
            }
        }

        return new DayContext(
            labels: array_values(array_unique($labels)),
            details: $details,
            filesChanged: $filesChanged,
        );
    }
}
