<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Events\SessionStopped;
use App\Models\Session;

class StopSession extends Action
{
    public function handle(Session $session): Session
    {
        $endedAt = now();
        $durationMinutes = (int) $session->started_at->diffInMinutes($endedAt);

        $session->update([
            'ended_at' => $endedAt,
            'duration_minutes' => $durationMinutes,
        ]);

        SessionStopped::dispatch($session->fresh());

        return $session->fresh();
    }
}
