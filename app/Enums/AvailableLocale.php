<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum AvailableLocale: string
{
    use EnhanceEnum;

    case French = 'fr';
    case English = 'en';
    case German = 'de';

    public function label(): string
    {
        return match ($this) {
            self::English => __('app.locales.en'),
            self::French => __('app.locales.fr'),
            self::German => __('app.locales.de'),
        };
    }
}
