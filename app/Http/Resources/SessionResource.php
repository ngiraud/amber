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
            'started_at_formatted' => $this->started_at?->format('H:i'),
            'ended_at_formatted' => $this->ended_at?->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            'rounded_minutes' => $this->rounded_minutes,
            'source' => $this->source->toArray(),
            'notes' => $this->notes,
            'description' => $this->description,
            'is_validated' => $this->is_validated,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'project' => ProjectResource::make($this->whenLoaded('project')),
        ];
    }
}
