<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'name' => $this->name,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'daily_reference_hours' => $this->daily_reference_hours,
            'rounding' => $this->rounding->toArray(),
            'hourly_rate' => $this->hourly_rate,
            'hourly_rate_formatted' => $this->hourly_rate !== null ? Number::currency($this->hourly_rate, 'EUR') : null,
            'daily_rate' => $this->daily_rate,
            'daily_rate_formatted' => $this->daily_rate !== null ? Number::currency($this->daily_rate, 'EUR') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'client' => ClientResource::make($this->whenLoaded('client')),
            'repositories' => ProjectRepositoryResource::collection($this->whenLoaded('repositories')),
        ];
    }
}
