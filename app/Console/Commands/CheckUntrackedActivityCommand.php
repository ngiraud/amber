<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\CheckUntrackedActivity;
use Illuminate\Console\Command;

class CheckUntrackedActivityCommand extends Command
{
    protected $signature = 'activity:check-untracked';

    protected $description = 'Check if untracked activity has exceeded the threshold to suggest starting a session';

    public function handle(CheckUntrackedActivity $checkUntrackedActivity): void
    {
        $checkUntrackedActivity->handle();
    }
}
