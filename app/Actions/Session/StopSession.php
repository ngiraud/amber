<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Events\SessionStopped;
use App\Models\Session;
use Carbon\CarbonImmutable;

class StopSession extends Action
{
    public function __construct(private readonly UpdateSession $updateSession) {}

    public function handle(Session $session): Session
    {
        $stopped = $this->updateSession->handle($session, new SessionData(
            endedAt: CarbonImmutable::now(),
        ));

        SessionStopped::dispatch($stopped);

        return $stopped;
    }
}
