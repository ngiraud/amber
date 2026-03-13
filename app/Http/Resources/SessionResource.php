<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Session
 */
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'date' => $this->date?->toDateString(),
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'duration_minutes' => $this->getDurationMinutes(),
            'rounded_minutes' => $this->getRoundedMinutes(),
            'source' => $this->source->toArray(),
            'notes' => $this->notes,
            'description' => $this->description,
            'is_validated' => $this->is_validated,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'project' => ProjectResource::make($this->whenLoaded('project')),
        ];
    }

    protected function getDurationMinutes(): int
    {
        if (! $this->resource->isActive()) {
            return $this->resource->duration_minutes ?? 0;
        }

        return (int) $this->resource->started_at->diffInMinutes(now());
    }

    protected function getRoundedMinutes(): int
    {
        if (! $this->resource->isActive() || ! $this->resource->relationLoaded('project')) {
            return $this->resource->rounded_minutes ?? 0;
        }

        return $this->resource->project->rounding->round($this->getDurationMinutes());
    }
}
