<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Actions\TimeEntry\RoundMinutes;
use App\Events\SessionStopped;
use App\Models\Session;

class StopSession extends Action
{
    public function __construct(private readonly RoundMinutes $roundMinutes) {}

    public function handle(Session $session): Session
    {
        $endedAt = now();
        $durationMinutes = (int) $session->started_at->diffInMinutes($endedAt);

        $session->loadMissing('project');

        $roundedMinutes = $this->roundMinutes->handle(
            $durationMinutes,
            $session->project->rounding,
        );

        $session->update([
            'ended_at' => $endedAt,
            'duration_minutes' => $durationMinutes,
            'rounded_minutes' => $roundedMinutes,
            'date' => $session->started_at->toDateString(),
        ]);

        $stopped = $session->fresh();

        SessionStopped::dispatch($stopped);

        return $stopped;
    }
}
