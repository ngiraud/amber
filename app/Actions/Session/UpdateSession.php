<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Actions\TimeEntry\RoundMinutes;
use App\Models\Session;
use Carbon\CarbonImmutable;

class UpdateSession extends Action
{
    public function __construct(private readonly RoundMinutes $roundMinutes) {}

    public function handle(Session $session, array $data): Session
    {
        $startedAt = isset($data['started_at'])
            ? CarbonImmutable::parse($data['started_at'])
            : CarbonImmutable::instance($session->started_at);

        $endedAt = isset($data['ended_at'])
            ? CarbonImmutable::parse($data['ended_at'])
            : ($session->ended_at ? CarbonImmutable::instance($session->ended_at) : null);

        $updates = [
            'started_at' => $startedAt,
            'date' => $startedAt->toDateString(),
        ];

        if ($endedAt !== null) {
            $durationMinutes = (int) $startedAt->diffInMinutes($endedAt);
            $session->loadMissing('project');
            $roundedMinutes = $this->roundMinutes->handle($durationMinutes, $session->project->rounding);

            $updates['ended_at'] = $endedAt;
            $updates['duration_minutes'] = $durationMinutes;
            $updates['rounded_minutes'] = $roundedMinutes;
        }

        if (array_key_exists('description', $data)) {
            $updates['description'] = $data['description'];
        }

        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }

        $session->update($updates);

        return $session->fresh();
    }
}
