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
            'source_type' => $this->source_type->toArray(),
            'type' => $this->type->toArray(),
            'occurred_at' => $this->occurred_at,
            'metadata' => $this->metadata,
            'project_name' => $this->whenLoaded('project', fn () => $this->project->name),
            'repository_name' => $this->whenLoaded('projectRepository', fn () => $this->projectRepository?->name),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'detail' => $this->detail,
            'occurred_at_formatted' => $this->occurred_at->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at_formatted' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
