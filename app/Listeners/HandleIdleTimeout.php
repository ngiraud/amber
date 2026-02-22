<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StopSession;
use App\Events\IdleTimeoutReached;
use Native\Desktop\Facades\Notification;

class HandleIdleTimeout
{
    public function __construct(private readonly StopSession $stopSession) {}

    public function handle(IdleTimeoutReached $event): void
    {
        $session = $event->session;
        $projectName = $session->project->name;

        $stopped = $this->stopSession->handle($session);

        $minutes = $stopped->duration_minutes ?? 0;
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        $duration = sprintf('%02d:%02d', $hours, $mins);

        Notification::title('Session Auto-Stopped')
            ->message("Session stopped after idle timeout. Tracked {$duration} for {$projectName}.")
            ->show();
    }
}
