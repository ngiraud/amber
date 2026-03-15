<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static> forActiveProjects()
 */
class ProjectRepository extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectRepositoryFactory> */
    use HasFactory, HasUlids;

    /**
     * Find the most specific repository whose path is a prefix of the given path.
     *
     * @param  Collection<int, self>  $repos
     */
    public static function findBestMatchForPath(Collection $repos, string $path): ?self
    {
        return $repos
            ->filter(fn (self $repo) => str_starts_with($path, $repo->local_path))
            ->sortByDesc(fn (self $repo) => mb_strlen($repo->local_path))
            ->first();
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

    #[Scope]
    protected function forActiveProjects(Builder $query): void
    {
        $query->whereHas('project', function (Builder $q) {
            /** @var \Illuminate\Database\Eloquent\Builder<Project> $q */
            return $q->active(); // @phpstan-ignore varTag.nativeType
        });
    }
}
