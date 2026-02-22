<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Data\ActivityEventData;
use App\Events\ActivityDetected;
use App\Events\ActivityWithoutSessionDetected;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;

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

        $activeSession ??= Session::findActive();

        if ($activeSession === null || $activeSession->project_id !== $projectId) {
            $project = Project::find($projectId);

            if ($project !== null) {
                ActivityWithoutSessionDetected::dispatch($project);
            }

            return null;
        }

        $event = ActivityEvent::create([
            'project_id' => $projectId,
            'project_repository_id' => $projectRepositoryId,
            'session_id' => $activeSession->id,
            'source_type' => $data->sourceType,
            'type' => $data->type,
            'occurred_at' => $data->occurredAt,
            'metadata' => $data->metadata,
        ]);

        ActivityDetected::dispatch($event);

        return $event;
    }
}
