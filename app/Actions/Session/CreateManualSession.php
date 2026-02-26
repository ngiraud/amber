<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Actions\TimeEntry\RoundMinutes;
use App\Enums\SessionSource;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;

class CreateManualSession extends Action
{
    public function __construct(private readonly RoundMinutes $roundMinutes) {}

    public function handle(
        Project $project,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
        ?string $description = null,
        ?string $notes = null,
    ): Session {
        $durationMinutes = (int) $startedAt->diffInMinutes($endedAt);
        $roundedMinutes = $this->roundMinutes->handle($durationMinutes, $project->rounding);

        return $project->sessions()->create([
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $durationMinutes,
            'rounded_minutes' => $roundedMinutes,
            'date' => $startedAt->toDateString(),
            'source' => SessionSource::Manual,
            'description' => $description,
            'notes' => $notes,
            'is_validated' => true,
        ]);
    }
}
