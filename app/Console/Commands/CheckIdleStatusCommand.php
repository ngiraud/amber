<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\CheckIdleStatus;
use Illuminate\Console\Command;

class CheckIdleStatusCommand extends Command
{
    protected $signature = 'activity:check-idle';

    protected $description = 'Check if the active session has been idle past the timeout threshold';

    public function handle(CheckIdleStatus $checkIdleStatus): void
    {
        $checkIdleStatus->handle();
    }
}
