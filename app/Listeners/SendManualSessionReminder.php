<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ManualSessionReminderReached;
use Native\Desktop\Facades\Notification;

class SendManualSessionReminder
{
    public function handle(ManualSessionReminderReached $event): void
    {
        $session = $event->session->loadMissing('project');
        $minutes = (int) $session->started_at->diffInMinutes(now());
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        $duration = sprintf('%02d:%02d', $hours, $mins);

        Notification::title('Session still running')
            ->message("You've been tracking {$session->project->name} for {$duration}. Still working?")
            ->reference($session->id)
            ->addAction('Stop Session')
            ->show();
    }
}
