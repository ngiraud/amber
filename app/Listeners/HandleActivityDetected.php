<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ActivityDetected;
use App\Models\Project;
use App\Models\Session;
use App\Services\MenuBarService;
use Native\Desktop\Facades\Notification;

class HandleActivityDetected
{
    public function __construct(private readonly MenuBarService $menuBarService) {}

    public function handle(ActivityDetected $event): void
    {
        if (Session::findActive() !== null) {
            return;
        }

        $projectId = $event->activityEvent->project_id;

        if ($projectId === null) {
            return;
        }

        $project = Project::find($projectId);

        if ($project === null) {
            return;
        }

        Notification::title('Activity Detected')
            ->message("Activity detected on {$project->name} — Start a session?")
            ->show();

        $this->menuBarService->refresh();
    }
}
