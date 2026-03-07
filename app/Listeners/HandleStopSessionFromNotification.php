<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StopSession;
use App\Models\Session;
use Native\Desktop\Events\Notifications\NotificationActionClicked;

class HandleStopSessionFromNotification
{
    public function __construct(private readonly StopSession $stopSession) {}

    public function handle(NotificationActionClicked $event): void
    {
        if ($event->index !== 0) {
            return;
        }

        $session = Session::find($event->reference);

        if ($session === null || $session->ended_at !== null) {
            return;
        }

        $this->stopSession->handle($session);
    }
}
