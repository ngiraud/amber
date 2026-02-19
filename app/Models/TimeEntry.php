<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TimeEntrySource;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    /** @use HasFactory<\Database\Factories\TimeEntryFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
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
     *   started_at: 'datetime',
     *   ended_at: 'datetime',
     *   source: class-string<TimeEntrySource>,
     *   is_validated: 'boolean',
     * }
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'source' => TimeEntrySource::class,
            'is_validated' => 'boolean',
        ];
    }
}
