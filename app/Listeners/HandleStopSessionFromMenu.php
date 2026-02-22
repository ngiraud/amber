<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StopSession;
use App\Events\Native\StopSessionFromMenu;
use App\Models\Session;

class HandleStopSessionFromMenu
{
    public function __construct(private readonly StopSession $stopSession) {}

    public function handle(StopSessionFromMenu $event): void
    {
        $active = Session::findActive();

        if ($active === null) {
            return;
        }

        $this->stopSession->handle($active);
    }
}
