<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionStarted;
use App\Events\SessionStopped;
use Native\Desktop\Facades\App;

class UpdateDockBadgeOnSessionChange
{
    public function handle(SessionStarted|SessionStopped $event): void
    {
        App::badgeCount($event instanceof SessionStarted ? 1 : 0);
    }
}
