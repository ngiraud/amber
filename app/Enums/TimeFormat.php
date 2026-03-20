<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum TimeFormat: string
{
    use EnhanceEnum;

    case TwentyFourHour = 'H:i';
    case TwelveHour = 'h:i A';

    public function label(): string
    {
        return match ($this) {
            self::TwentyFourHour => '24h (14:30)',
            self::TwelveHour => '12h (02:30 PM)',
        };
    }
}
