<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IdleTimeoutReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Session $session,
        public CarbonImmutable $lastActivityAt,
    ) {}
}
