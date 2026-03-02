<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Session\ReconstructDailySessions;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReconstructSessionsCommand extends Command
{
    protected $signature = 'sessions:reconstruct {--date= : The date to reconstruct sessions for (defaults to today)}';

    protected $description = 'Reconstruct sessions from activity events for a given date';

    public function handle(ReconstructDailySessions $action): void
    {
        $date = $this->option('date') !== null
            ? CarbonImmutable::parse($this->option('date'))
            : CarbonImmutable::today();

        $sessions = $action->handle($date);

        $this->info("Reconstructed {$sessions->count()} session(s) for {$date->toDateString()}.");
    }
}
