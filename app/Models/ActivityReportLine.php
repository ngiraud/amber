<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReportLine extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityReportLineFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<ActivityReport, $this>
     */
    public function activityReport(): BelongsTo
    {
        return $this->belongsTo(ActivityReport::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array{
     *   date: 'date',
     * }
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
