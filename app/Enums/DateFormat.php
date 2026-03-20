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
            self::DayMonthYear => 'DD/MM/YYYY',
            self::MonthDayYear => 'MM/DD/YYYY',
            self::YearMonthDay => 'YYYY-MM-DD',
            self::DayShortMonthYear => 'DD Mon YYYY',
        };
    }
}
