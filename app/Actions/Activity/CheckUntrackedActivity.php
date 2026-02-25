<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Events\UntrackedActivityThresholdReached;
use App\Models\ActivityEvent;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class CheckUntrackedActivity extends Action
{
    public function handle(): void
    {
        if (Session::findActive() !== null) {
            return;
        }

        $thresholdMinutes = config('activity.untracked_threshold_minutes');

        $oldestUntrackedAt = ActivityEvent::query()
            ->whereNull('session_id')
            ->where('occurred_at', '>=', CarbonImmutable::now()->subMinutes($thresholdMinutes * 2))
            ->oldest('occurred_at')
            ->value('occurred_at');

        if ($oldestUntrackedAt === null) {
            return;
        }

        if ($oldestUntrackedAt->diffInMinutes(CarbonImmutable::now()) < $thresholdMinutes) {
            return;
        }

        if (Cache::has('untracked_activity_threshold_notified')) {
            return;
        }

        $topResult = ActivityEvent::query()
            ->with('project')
            ->whereNull('session_id')
            ->whereNotNull('project_id')
            ->where('occurred_at', '>=', CarbonImmutable::now()->subMinutes($thresholdMinutes * 2))
            ->selectRaw('project_id, COUNT(*) as event_count')
            ->groupBy('project_id')
            ->orderByDesc('event_count')
            ->first();

        if ($topResult === null || is_null($topResult->project)) {
            return;
        }

        Cache::put('untracked_activity_threshold_notified', true, now()->addMinutes($thresholdMinutes));

        UntrackedActivityThresholdReached::dispatch($topResult->project, (int) $topResult->event_count);
    }
}
