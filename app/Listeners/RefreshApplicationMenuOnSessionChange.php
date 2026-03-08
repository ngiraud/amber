<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionStarted;
use App\Events\SessionStopped;
use App\Services\ApplicationMenuService;

class RefreshApplicationMenuOnSessionChange
{
    public function __construct(private readonly ApplicationMenuService $applicationMenuService) {}

    public function handle(SessionStarted|SessionStopped $event): void
    {
        $this->applicationMenuService->build();
    }
}
