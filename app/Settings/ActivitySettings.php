<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ActivitySettings extends Settings
{
    public int $idle_timeout_minutes;

    public int $untracked_threshold_minutes;

    public int $scan_interval_minutes;

    public int $block_end_padding_minutes;

    public static function group(): string
    {
        return 'activity';
    }
}
