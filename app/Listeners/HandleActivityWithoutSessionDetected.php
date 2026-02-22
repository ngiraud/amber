<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ActivityWithoutSessionDetected;
use Native\Desktop\Facades\Notification;

class HandleActivityWithoutSessionDetected
{
    public function handle(ActivityWithoutSessionDetected $event): void
    {
        Notification::title('Activity Detected')
            ->message("Activity on {$event->project->name} — start a session?")
            ->reference($event->project->id)
            ->addAction('Start Session')
            ->show();
    }
}
