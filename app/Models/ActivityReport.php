<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityReportStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityReport extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityReportFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<ActivityReportLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ActivityReportLine::class);
    }

    /** @return Attribute<float|null, int|null> */
    protected function totalAmountHt(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value) => is_null($value) ? null : $value / 100,
            set: fn (?float $value) => is_null($value) ? null : (int) round($value * 100),
        );
    }

    #[Scope]
    protected function forPeriod(Builder $query, int $month, int $year): void
    {
        $query->where('month', $month)->where('year', $year);
    }

    /**
     * @return array{
     *   status: 'App\\Enums\\ActivityReportStatus',
     *   generated_at: 'datetime',
     * }
     */
    protected function casts(): array
    {
        return [
            'status' => ActivityReportStatus::class,
            'generated_at' => 'datetime',
        ];
    }
}
