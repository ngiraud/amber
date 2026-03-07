<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityReport
 */
class ActivityReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'month' => $this->month,
            'year' => $this->year,
            'status' => $this->status->toArray(),
            'total_minutes' => $this->total_minutes,
            'total_days' => (float) $this->total_days,
            'total_amount_ht' => $this->total_amount_ht,
            'generated_at' => $this->generated_at,
            'pdf_path' => $this->pdf_path,
            'csv_path' => $this->csv_path,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'client' => ClientResource::make($this->whenLoaded('client')),
            'lines' => ActivityReportLineResource::collection($this->whenLoaded('lines')),
            'lines_count' => $this->whenCounted('lines'),
        ];
    }
}
