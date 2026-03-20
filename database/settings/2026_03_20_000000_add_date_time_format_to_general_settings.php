<?php

declare(strict_types=1);

use App\Enums\DateFormat;
use App\Enums\TimeFormat;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.date_format', DateFormat::DayMonthYear->value);
        $this->migrator->add('general.time_format', TimeFormat::TwentyFourHour->value);
    }
};
