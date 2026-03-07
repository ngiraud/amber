<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Enums\SessionSource;
use App\Events\ManualSessionReminderReached;
use App\Models\Session;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class CheckManualSessionReminder extends Action
{
    public function __construct(private readonly ActivitySettings $settings) {}

    public function handle(): void
    {
        $session = Session::findActive();

        if ($session === null || $session->source !== SessionSource::Manual) {
            return;
        }

        $reminderMinutes = $this->settings->manual_session_reminder_minutes;

        if ($reminderMinutes <= 0) {
            return;
        }

        $elapsedMinutes = (int) $session->started_at->diffInMinutes(CarbonImmutable::now());
        $currentInterval = intdiv($elapsedMinutes, $reminderMinutes);

        if ($currentInterval < 1) {
            return;
        }

        $cacheKey = "session_reminder:{$session->id}:{$currentInterval}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes($reminderMinutes));

        ManualSessionReminderReached::dispatch($session);
    }
}
