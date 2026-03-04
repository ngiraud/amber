<?php

declare(strict_types=1);

namespace App\Services\ActivitySources\Contracts;

use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Models\ProjectRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface ActivitySource
{
    /** Unique identifier for this source (e.g., 'git', 'claude-code'). */
    public function identifier(): ActivityEventSourceType;

    /**
     * Scan for activity events since the given timestamp.
     *
     * @param  Collection<int, ProjectRepository>  $repos  Active project repositories (pre-fetched once by the caller)
     * @return Collection<int, ActivityEventData>
     */
    public function scan(CarbonImmutable $since, Collection $repos): Collection;

    /** Whether this source is available/configured on the current system. */
    public function isAvailable(): bool;
}
