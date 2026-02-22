<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionStarted;
use App\Events\SessionStopped;
use Native\Desktop\Facades\Notification;

class SendSessionNotification
{
    public function handle(SessionStarted|SessionStopped $event): void
    {
        if ($event instanceof SessionStarted) {
            Notification::title('Session Started')
                ->message("Tracking time for {$event->session->project->name}")
                ->show();

            return;
        }

        $minutes = $event->session->duration_minutes ?? 0;
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        $duration = sprintf('%02d:%02d', $hours, $mins);

        Notification::title('Session Stopped')
            ->message("Tracked {$duration} for {$event->session->project->name}")
            ->show();
    }
}
