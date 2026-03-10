<?php

declare(strict_types=1);

use App\Enums\AvailableLocale;
use App\Enums\RoundingStrategy;
use Native\Desktop\Enums\SystemThemesEnum;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.company_name', null);
        $this->migrator->add('general.company_address', null);
        $this->migrator->add('general.default_hourly_rate', null);
        $this->migrator->add('general.default_daily_rate', null);
        $this->migrator->add('general.default_daily_reference_hours', 7);
        $this->migrator->add('general.default_rounding_strategy', RoundingStrategy::Quarter->value);
        $this->migrator->add('general.timezone', 'Europe/Paris');
        $this->migrator->add('general.locale', AvailableLocale::English->value);
        $this->migrator->add('general.theme', SystemThemesEnum::SYSTEM->value);
        $this->migrator->add('general.open_at_login', false);
    }
};
