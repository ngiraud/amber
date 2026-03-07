<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityReportLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityReportLine
 */
class ActivityReportLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_report_id' => $this->activity_report_id,
            'project_id' => $this->project_id,
            'date' => $this->date?->toDateString(),
            'minutes' => $this->minutes,
            'days' => (float) $this->days,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'project' => ProjectResource::make($this->whenLoaded('project')),
        ];
    }
}
