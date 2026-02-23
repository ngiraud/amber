<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ActivityDetected;

class HandleActivityDetected
{
    //    public function __construct(private readonly MenuBarService $menuBarService) {}

    public function handle(ActivityDetected $event): void
    {
        //        if (Session::hasActive()) {
        //            return;
        //        }
        //
        //        $projectId = $event->activityEvent->project_id;
        //
        //        if ($event->activityEvent->project_id === null) {
        //            return;
        //        }
        //
        //        $project = Project::find($projectId);
        //
        //        if ($project === null) {
        //            return;
        //        }

        // @TODO: For now we do not want to trigger a notification inviting the user to start a session
        // The notification should be displayed after 15min or something like that
        //        Notification::title('Activity Detected')
        //            ->message("Activity detected on {$project->name} — Start a session?")
        //            ->show();
        //
        //        $this->menuBarService->refresh();
    }
}
