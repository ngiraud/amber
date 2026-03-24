<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum RoundingStrategy: int
{
    use EnhanceEnum;

    case Quarter = 15;
    case HalfHour = 30;
    case Hour = 60;

    public function label(): string
    {
        return match ($this) {
            self::Quarter => __('app.settings.rounding.quarter_hour'),
            self::HalfHour => __('app.settings.rounding.half_hour'),
            self::Hour => __('app.settings.rounding.hour'),
        };
    }

    public function round(int $rawMinutes): int
    {
        if ($rawMinutes <= 0) {
            return 0;
        }

        return (int) ceil($rawMinutes / $this->value) * $this->value;
    }
}
