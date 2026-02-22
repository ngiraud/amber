<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\ScanAllSources;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ScanActivitySourcesCommand extends Command
{
    protected $signature = 'activity:scan';

    protected $description = 'Scan all activity sources and record detected events';

    public function handle(ScanAllSources $scanAllSources): void
    {
        $interval = (int) config('activity.scan_interval_minutes', 5);
        $since = CarbonImmutable::now()->subMinutes($interval + 1);

        $events = $scanAllSources->handle($since);

        $this->info("Recorded {$events->count()} activity event(s).");
    }
}
