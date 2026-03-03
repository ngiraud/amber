<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityEventFactory> */
    use HasFactory, HasUlids;

    protected $perPage = 50;

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<ProjectRepository, $this>
     */
    public function projectRepository(): BelongsTo
    {
        return $this->belongsTo(ProjectRepository::class);
    }

    /**
     * @return BelongsTo<Session, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return Attribute<string, never> */
    protected function detail(): Attribute
    {
        return Attribute::get(fn () => $this->type->parseDetailsFromMetadata($this->metadata));
    }

    /**
     * @return array{
     *   source_type: 'App\\Enums\\ActivityEventSourceType',
     *   type: 'App\\Enums\\ActivityEventType',
     *   occurred_at: 'datetime',
     *   metadata: 'array'
     * }
     */
    protected function casts(): array
    {
        return [
            'source_type' => ActivityEventSourceType::class,
            'type' => ActivityEventType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
