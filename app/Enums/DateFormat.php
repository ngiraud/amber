<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum DateFormat: string
{
    use EnhanceEnum;

    case DayMonthYear = 'd/m/Y';
    case MonthDayYear = 'm/d/Y';
    case YearMonthDay = 'Y-m-d';
    case DayShortMonthYear = 'd M Y';

    public function label(): string
    {
        return match ($this) {
            self::DayMonthYear => '31/01/2026',
            self::MonthDayYear => '01/31/2026',
            self::YearMonthDay => '2026-01-31',
            self::DayShortMonthYear => '31 Jan 2026',
        };
    }
}
