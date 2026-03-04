<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum AvailableLocale: string
{
    use EnhanceEnum;

    case French = 'fr';
    case English = 'en';

    public function label(): string
    {
        return match ($this) {
            self::English => __('English'),
            self::French => __('French'),
        };
    }
}
