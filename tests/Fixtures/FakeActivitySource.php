<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\ActivityEventSourceType;
use App\Services\ActivitySources\Contracts\ActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FakeActivitySource implements ActivitySource
{
    public function __construct(
        protected ActivityEventSourceType $id = ActivityEventSourceType::Git,
        protected bool $available = true,
        protected array $events = [],
    ) {}

    public function identifier(): ActivityEventSourceType
    {
        return $this->id;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function scan(CarbonImmutable $since, CarbonImmutable $until, Collection $repos): Collection
    {
        return collect($this->events);
    }

    public function setEvents(array $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }
}
