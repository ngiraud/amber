<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Activity\ScanActivitySources;
use App\Enums\ActivityEventSourceType;
use App\Events\ActivityBackfillCompleted;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class ScanActivitySourcesCommand extends Command
{
    protected $signature = 'activity:scan {source?} {--since= : Relative time string (e.g., "1 hour ago")}';

    protected $description = 'Scan activity sources and record detected events';

    public function handle(ScanActivitySources $scanActivitySources, ActivitySettings $settings): void
    {
        $sourceName = $this->argument('source');
        $sourceType = $sourceName ? ActivityEventSourceType::tryFrom((string) $sourceName) : null;

        if ($sourceName && ! $sourceType) {
            $this->error("Invalid source: {$sourceName}");

            return;
        }

        $defaultSince = CarbonImmutable::now()->subMinutes($settings->scan_interval_minutes + 1);
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
        $events = $scanActivitySources->handle($since, $until, $sources);

        $settings->last_scan_completed_at = $until;
        $settings->save();

        $this->info("Recorded {$events->count()} activity event(s).");

        if ($isBackfill && $events->isNotEmpty()) {
            Event::dispatch(new ActivityBackfillCompleted(
                eventsCount: $events->count(),
                period: $since->diffAsCarbonInterval($until)->cascade()->forHumans(),
                since: $since->toDateString(),
            ));
        }
    }
}
