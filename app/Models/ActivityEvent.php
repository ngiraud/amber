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
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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
        return Attribute::make(
            get: function () {
                if ($this->source_type === ActivityEventSourceType::Git) {
                    return Str::of($this->metadata['message'])
                        ->when(
                            ! empty($this->metadata['hash']),
                            fn (Stringable $str) => $str->prepend(sprintf('[%s] ', Str::of($this->metadata['hash'])->substr(0, 7)->toString()))
                        )
                        ->toString();
                }

                if ($this->source_type === ActivityEventSourceType::Fswatch) {
                    return $this->metadata['file_path'];
                }

                if ($this->type === ActivityEventType::ClaudeFileTouch) {
                    return $this->metadata['file_path'];
                }

                return '';
            },
        );
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
