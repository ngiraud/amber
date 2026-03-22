<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StopSession;
use App\Enums\SessionSource;
use App\Models\Session;
use Native\Desktop\Events\PowerMonitor\ScreenLocked;

class HandleScreenLocked
{
    public function __construct(private readonly StopSession $stopSession) {}

    public function handle(ScreenLocked $event): void
    {
        $session = Session::findActive();

        if ($session === null || $session->source !== SessionSource::Manual) {
            return;
        }

        $this->stopSession->handle($session);
    }
}
