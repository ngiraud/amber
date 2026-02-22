<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SessionSource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory, HasUlids;

    public static function findActive(array $relationships = []): ?static
    {
        return static::query()->active()->with($relationships)->first();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ActivityEvent, $this>
     */
    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class);
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * @return array{
     *   started_at: 'datetime',
     *   ended_at: 'datetime',
     *   source: 'App\\Enums\\SessionSource',
     *   is_validated: 'boolean',
     * }
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'source' => SessionSource::class,
            'is_validated' => 'boolean',
        ];
    }
}
