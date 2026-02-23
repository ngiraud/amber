<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Data\ActivityEventData;
use App\Events\ActivityDetected;
use App\Models\ActivityEvent;
use App\Models\Session;

class RecordActivityEvent extends Action
{
    public function handle(ActivityEventData $data, ?Session $activeSession = null): ?ActivityEvent
    {
        $projectRepositoryId = $data->projectRepository->id;
        $projectId = $data->projectRepository->project_id;

        // Not sure about this yet, because we record an event each time a file has been "watched" if activated
        //        $activeSession ??= Session::findActive();

        $sessionId = ($activeSession?->project_id === $projectId) ? $activeSession->id : null;

        $event = ActivityEvent::firstOrCreate(
            [
                'project_id' => $projectId,
                'project_repository_id' => $projectRepositoryId,
                'source_type' => $data->sourceType,
                'type' => $data->type,
                'occurred_at' => $data->occurredAt,
            ],
            [
                'session_id' => $sessionId,
                'metadata' => $data->metadata,
            ]
        );

        if (! $event->wasRecentlyCreated) {
            return null;
        }

        ActivityDetected::dispatch($event);

        return $event;
    }
}
