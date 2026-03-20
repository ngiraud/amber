<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Native\Desktop\Facades\AutoUpdater;

class CheckForUpdatesCommand extends Command
{
    protected $signature = 'updates:check';

    protected $description = 'Check for application updates';

    public function handle(): void
    {
        if (! config('nativephp.updater.enabled', true)) {
            return;
        }

        AutoUpdater::checkForUpdates();
    }
}
