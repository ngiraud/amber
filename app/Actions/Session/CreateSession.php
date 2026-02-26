<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Enums\SessionSource;
use App\Models\Project;
use App\Models\Session;
use App\Services\TimeEntryService;
use Carbon\CarbonImmutable;

class CreateSession extends Action
{
    protected SessionSource $source = SessionSource::Manual;

    public function __construct(private readonly TimeEntryService $timeEntryService) {}

    public function handle(Project $project, SessionData $data): Session
    {
        $startedAt = $data->startedAt ?? CarbonImmutable::now();

        return $project->sessions()->create([
            'started_at' => $startedAt,
            'source' => $this->source,
            'description' => $data->description,
            'notes' => $data->notes,
            'is_validated' => false,

            ...$data->endedAt !== null ? [
                'ended_at' => $data->endedAt,
                'duration_minutes' => $durationMinutes = (int) $startedAt->diffInMinutes($data->endedAt),
                'rounded_minutes' => $this->timeEntryService->roundMinutesAccordingStrategy($durationMinutes, $project->rounding),
                'date' => $startedAt->toDateString(),
                'is_validated' => true,
            ] : [],
        ]);
    }

    public function manual(): static
    {
        $this->source = SessionSource::Manual;

        return $this;
    }

    public function auto(): static
    {
        $this->source = SessionSource::Auto;

        return $this;
    }

    public function reconstructed(): static
    {
        $this->source = SessionSource::Reconstructed;

        return $this;
    }
}
