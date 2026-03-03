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
                return match ($this->type) {
                    ActivityEventType::GitCommit => Str::of($this->metadata['message'] ?? '')
                        ->when(
                            ! empty($this->metadata['hash']),
                            fn (Stringable $str) => $str->prepend(sprintf('[%s] ', Str::of($this->metadata['hash'])->substr(0, 7)->toString()))
                        )
                        ->when(
                            ! empty($this->metadata['branch']),
                            fn (Stringable $str) => $str->append(sprintf(' (%s)', $this->metadata['branch']))
                        )
                        ->toString(),
                    ActivityEventType::GitBranchSwitch => sprintf(
                        '%s → %s',
                        $this->metadata['from_branch'] ?? '',
                        $this->metadata['to_branch'] ?? '',
                    ),
                    ActivityEventType::GitPrOpened,
                    ActivityEventType::GitPrMerged => sprintf(
                        'PR #%s: %s',
                        $this->metadata['number'] ?? '',
                        $this->metadata['title'] ?? '',
                    ),
                    ActivityEventType::FileChange => $this->metadata['file_path'] ?? '',
                    ActivityEventType::ClaudeFileTouch => $this->metadata['file_path'] ?? '',
                    ActivityEventType::ClaudeUserPrompt => mb_substr($this->metadata['prompt'] ?? '', 0, 80),
                    default => '',
                };
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
