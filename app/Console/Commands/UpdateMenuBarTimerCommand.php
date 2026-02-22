<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\MenuBarService;
use Illuminate\Console\Command;

class UpdateMenuBarTimerCommand extends Command
{
    protected $signature = 'menubar:update-timer';

    protected $description = 'Update the menu bar elapsed time label for the active session';

    public function handle(MenuBarService $menuBarService): void
    {
        $menuBarService->updateLabel();
    }
}
