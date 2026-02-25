<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionAlreadyActiveAttempted;
use App\Events\SessionStarted;
use App\Events\SessionStopped;
use InvalidArgumentException;
use Native\Desktop\Facades\Notification;

class SendSessionNotification
{
    public function handle(SessionStarted|SessionStopped|SessionAlreadyActiveAttempted $event): void
    {
        match (get_class($event)) {
            SessionStarted::class => $this->handleSessionStarted($event),
            SessionStopped::class => $this->handleSessionStopped($event),
            SessionAlreadyActiveAttempted::class => $this->handleSessionAlreadyActive($event),
            default => throw new InvalidArgumentException('Invalid event type'),
        };
    }

    protected function handleSessionStarted(SessionStarted $event): void
    {
        Notification::title('Session Started')
            ->message("Tracking time for {$event->session->project->name}")
            ->show();
    }

    protected function handleSessionStopped(SessionStopped $event): void
    {
        $minutes = $event->session->duration_minutes ?? 0;
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        $duration = sprintf('%02d:%02d', $hours, $mins);

        Notification::title('Session Stopped')
            ->message("Tracked {$duration} for {$event->session->project->name}")
            ->show();
    }

    protected function handleSessionAlreadyActive(SessionAlreadyActiveAttempted $event): void
    {
        Notification::title('Session Already Active')
            ->message("A session is already running for {$event->session->project->name}")
            ->show();
    }
}
