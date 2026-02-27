<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Models\Session;

class UpdateSession extends Action
{
    public function handle(Session $session, SessionData $data): Session
    {
        $startedAt = $data->startedAt ?? $session->started_at;
        $endedAt = $data->endedAt ?? $session->ended_at;

        $session->update([
            'started_at' => $startedAt,
            'date' => $startedAt->toDateString(),
            'description' => $data->description ?? $session->description,
            'notes' => $data->notes ?? $session->notes,

            ...$endedAt !== null ? [
                'ended_at' => $endedAt,
                'duration_minutes' => $durationMinutes = (int) $startedAt->diffInMinutes($endedAt),
                'rounded_minutes' => $session->project->rounding->round($durationMinutes),
            ] : [],
        ]);

        return $session->fresh();
    }
}
