<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoundingStrategy;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<ProjectRepository, $this>
     */
    public function repositories(): HasMany
    {
        return $this->hasMany(ProjectRepository::class);
    }

    /**
     * @return HasMany<Session, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
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
        $query->where('is_active', true);
    }

    /** @return Attribute<float|null, int|null> */
    protected function hourlyRate(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value) => is_null($value) ? null : $value / 100,
            set: fn (?float $value) => is_null($value) ? null : (int) round($value * 100),
        );
    }

    /** @return Attribute<float|null, int|null> */
    protected function dailyRate(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value) => is_null($value) ? null : $value / 100,
            set: fn (?float $value) => is_null($value) ? null : (int) round($value * 100),
        );
    }

    /**
     * @return array{
     *   is_active: 'boolean',
     * }
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rounding' => RoundingStrategy::class,
        ];
    }
}
