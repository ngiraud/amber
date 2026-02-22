<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Events\IdleTimeoutReached;
use App\Models\ActivityEvent;
use App\Models\Session;
use Carbon\CarbonImmutable;

class CheckIdleStatus extends Action
{
    public function handle(): void
    {
        $session = Session::findActive(['project']);

        if ($session === null) {
            return;
        }

        $lastEvent = ActivityEvent::query()
            ->where('project_id', $session->project_id)
            ->latest('occurred_at')
            ->first();

        $lastActivityAt = $lastEvent !== null
            ? CarbonImmutable::instance($lastEvent->occurred_at)
            : CarbonImmutable::instance($session->started_at);

        $idleMinutes = $lastActivityAt->diffInMinutes(CarbonImmutable::now());

        if ($idleMinutes >= config('activity.idle_timeout_minutes')) {
            IdleTimeoutReached::dispatch($session, $lastActivityAt);
        }
    }
}
