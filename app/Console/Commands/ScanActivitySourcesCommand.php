<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\ScanActivitySources;
use App\Actions\Session\ReconstructSessionsFromDate;
use App\Enums\ActivityEventSourceType;
use App\Events\ActivityBackfillCompleted;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class ScanActivitySourcesCommand extends Command
{
    /** Lookback window in minutes: scan runs every minute, +2 absorbs cron jitter */
    private const int SCAN_WINDOW_MINUTES = 3;

    protected $signature = 'activity:scan {source?} {--since= : Relative time string (e.g., "1 hour ago")}';

    protected $description = 'Scan activity sources and record detected events';

    public function handle(ScanActivitySources $scanActivitySources, ReconstructSessionsFromDate $reconstructSessionsFromDate, ActivitySettings $settings): void
    {
        $sourceName = $this->argument('source');
        $sourceType = $sourceName ? ActivityEventSourceType::tryFrom((string) $sourceName) : null;

        if ($sourceName && ! $sourceType) {
            $this->error("Invalid source: {$sourceName}");

            return;
        }

        $defaultSince = CarbonImmutable::now()->subMinutes(self::SCAN_WINDOW_MINUTES);

        $sinceOption = $this->option('since');
        $isBackfill = false;

        if ($sinceOption) {
            $since = CarbonImmutable::parse((string) $sinceOption);
        } elseif ($settings->last_scan_completed_at?->isBefore($defaultSince)) {
            $since = $settings->last_scan_completed_at;
            $isBackfill = true;
        } else {
            $since = $defaultSince;
        }

        $until = CarbonImmutable::now();

        $this->info("Scanning activity since {$since->toDateTimeString()}...");

        $sources = $sourceType ? collect([$sourceType]) : null;
        $result = $scanActivitySources->handle($since, $until, $sources);

        $settings->last_scan_completed_at = $until;
        $settings->save();

        $this->info("Recorded {$result->count()} activity event(s).");

        if ($isBackfill && $result->isNotEmpty()) {
            $sessions = $reconstructSessionsFromDate->handle($since->startOfDay());

            Event::dispatch(new ActivityBackfillCompleted(
                eventsCount: $result->count(),
                sessionsCount: $sessions->count(),
                period: $since->diffAsCarbonInterval($until)->cascade()->forHumans(),
                since: $since->toDateString(),
            ));
        }
    }
}
