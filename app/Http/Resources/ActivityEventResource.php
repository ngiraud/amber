<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityEvent
 */
class ActivityEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_repository_id' => $this->project_repository_id,
            'session_id' => $this->session_id,
            'source_type' => $this->source_type,
            'type' => $this->type->toArray(),
            'occurred_at' => $this->occurred_at,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
