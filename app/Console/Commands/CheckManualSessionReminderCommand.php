<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Session\CheckManualSessionReminder;
use Illuminate\Console\Command;

class CheckManualSessionReminderCommand extends Command
{
    protected $signature = 'session:check-reminder';

    protected $description = 'Check if a manual timer session has been running long enough to send a reminder notification';

    public function handle(CheckManualSessionReminder $action): void
    {
        $action->handle();
    }
}
