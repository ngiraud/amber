<?php

declare(strict_types=1);

namespace App\Settings;

use App\Enums\AvailableLocale;
use App\Enums\RoundingStrategy;
use Native\Desktop\Enums\SystemThemesEnum;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $company_name;

    public ?string $company_address;

    public ?float $default_hourly_rate;

    public ?float $default_daily_rate;

    public int $default_daily_reference_hours;

    public RoundingStrategy $default_rounding_strategy;

    public string $timezone;

    public AvailableLocale $locale;

    public SystemThemesEnum $theme;

    public static function group(): string
    {
        return 'general';
    }
}
