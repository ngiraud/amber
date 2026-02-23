<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\ScanAllSources;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanActivitySourcesCommand extends Command
{
    protected $signature = 'activity:scan';

    protected $description = 'Scan all activity sources and record detected events';

    public function handle(ScanAllSources $scanAllSources): void
    {
        $interval = config()->integer('activity.scan_interval_minutes');
        $since = CarbonImmutable::now()->subMinutes($interval + 1);

        Log::channel('activity')->info('[activity:scan] Starting scan', ['since' => $since->toIso8601String()]);

        $events = $scanAllSources->handle($since);

        Log::channel('activity')->info('[activity:scan] Scan complete', ['recorded' => $events->count()]);

        $this->info("Recorded {$events->count()} activity event(s).");
    }
}
