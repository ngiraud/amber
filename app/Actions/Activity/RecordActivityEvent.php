<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Data\ActivityEventData;
use App\Events\ActivityDetected;
use App\Models\ActivityEvent;
use App\Models\Session;
use Illuminate\Support\Facades\Log;

class RecordActivityEvent extends Action
{
    public function __construct(
        protected DetectActiveProject $detectActiveProject,
    ) {}

    public function handle(ActivityEventData $data, ?Session $activeSession = null): ?ActivityEvent
    {
        $projectId = $data->projectId;
        $projectRepositoryId = $data->projectRepositoryId;

        if ($projectId === null && $data->filePath !== null) {
            $resolved = $this->detectActiveProject->handle($data->filePath);
            $projectId = $resolved['project_id'];
            $projectRepositoryId = $resolved['project_repository_id'];
        }

        if ($projectId === null) {
            return null;
        }

        // Not sure about this yet, because we record an event each time a file has been "watched" if activated
        //        $activeSession ??= Session::findActive();

        $sessionId = ($activeSession?->project_id === $projectId) ? $activeSession->id : null;

        $event = ActivityEvent::firstOrCreate(
            [
                'project_id' => $projectId,
                'type' => $data->type,
                'source_type' => $data->sourceType,
                'occurred_at' => $data->occurredAt,
            ],
            [
                'project_repository_id' => $projectRepositoryId,
                'session_id' => $sessionId,
                'metadata' => $data->metadata,
            ]
        );

        Log::channel('activity')->info('[activity:scan] Event '.($event->wasRecentlyCreated ? 'recorded' : 'skipped (duplicate)'), [
            'type' => $data->type->value,
            'source' => $data->sourceType,
            'project_id' => $projectId,
            'session_id' => $sessionId,
            'occurred_at' => $data->occurredAt->toIso8601String(),
        ]);

        if (! $event->wasRecentlyCreated) {
            return null;
        }

        ActivityDetected::dispatch($event);

        return $event;
    }
}
