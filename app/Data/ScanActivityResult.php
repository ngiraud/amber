<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ActivityEvent;
use Illuminate\Support\Collection;

final class ScanActivityResult
{
    /**
     * @param  Collection<int, ActivityEvent>  $events
     * @param  Collection<int, string>  $errors
     */
    public function __construct(
        public readonly Collection $events,
        public readonly Collection $errors,
    ) {}

    public function count(): int
    {
        return $this->events->count();
    }

    public function isNotEmpty(): bool
    {
        return $this->events->isNotEmpty();
    }
}
