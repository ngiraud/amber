<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UntrackedActivityThresholdReached;
use Native\Desktop\Facades\Notification;

class HandleUntrackedActivityThreshold
{
    public function handle(UntrackedActivityThresholdReached $event): void
    {
        Notification::title('Activity Detected')
            ->message("You've been working on {$event->project->name} without a session. Start one now?")
            ->reference($event->project->id)
            ->addAction('Start Session')
            ->show();
    }
}
