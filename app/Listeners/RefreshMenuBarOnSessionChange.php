<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionStarted;
use App\Events\SessionStopped;
use App\Services\MenuBarService;

class RefreshMenuBarOnSessionChange
{
    public function __construct(private readonly MenuBarService $menuBarService) {}

    public function handle(SessionStarted|SessionStopped $event): void
    {
        $this->menuBarService->refresh();
    }
}
