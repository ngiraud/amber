<?php

declare(strict_types=1);

namespace App\Settings;

use Carbon\CarbonImmutable;
use Spatie\LaravelSettings\Settings;

class ActivitySettings extends Settings
{
    public int $idle_timeout_minutes;

    public int $scan_interval_minutes;

    public int $block_end_padding_minutes;

    public int $manual_session_reminder_minutes;

    public ?CarbonImmutable $last_scan_completed_at;

    public static function group(): string
    {
        return 'activity';
    }
}
