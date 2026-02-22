<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\MenuBarService;

class RefreshMenuBarOnSessionChange
{
    public function __construct(private readonly MenuBarService $menuBarService) {}

    public function handle(): void
    {
        $this->menuBarService->refresh();
    }
}
