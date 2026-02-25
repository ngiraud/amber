<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UntrackedActivityThresholdReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Project $project,
        public int $eventCount,
    ) {}
}
