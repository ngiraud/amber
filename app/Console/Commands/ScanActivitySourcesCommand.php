<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\ScanActivitySources;
use App\Enums\ActivityEventSourceType;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ScanActivitySourcesCommand extends Command
{
    protected $signature = 'activity:scan {source?} {--since= : Relative time string (e.g., "1 hour ago")}';

    protected $description = 'Scan activity sources and record detected events';

    public function handle(ScanActivitySources $scanActivitySources, ActivitySettings $settings): void
    {
        $sinceValue = $this->option('since');

        if ($sinceValue) {
            $since = CarbonImmutable::parse((string) $sinceValue);
        } else {
            $interval = $settings->scan_interval_minutes;
            $since = CarbonImmutable::now()->subMinutes($interval + 1);
        }

        $until = CarbonImmutable::now();

        $sourceName = $this->argument('source');
        $sourceType = $sourceName ? ActivityEventSourceType::tryFrom((string) $sourceName) : null;

        if ($sourceName && ! $sourceType) {
            $this->error("Invalid source: {$sourceName}");

            return;
        }

        $this->info("Scanning activity since {$since->toDateTimeString()}...");

        $sources = $sourceType ? collect([$sourceType]) : null;
        $events = $scanActivitySources->handle($since, $until, $sources);

        $this->info("Recorded {$events->count()} activity event(s).");
    }
}
