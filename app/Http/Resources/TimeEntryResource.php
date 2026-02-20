<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TimeEntry
 */
class TimeEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'project_id' => $this->project_id,
            'date' => $this->date,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'raw_minutes' => $this->raw_minutes,
            'rounded_minutes' => $this->rounded_minutes,
            'source' => $this->source->toArray(),
            'description' => $this->description,
            'is_validated' => $this->is_validated,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
