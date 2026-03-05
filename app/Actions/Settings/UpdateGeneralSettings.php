<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Enums\AvailableLocale;
use App\Enums\RoundingStrategy;
use App\Settings\GeneralSettings;

class UpdateGeneralSettings extends Action
{
    public function __construct(protected readonly GeneralSettings $settings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $this->settings->company_name = $data['company_name'] ?? null;
        $this->settings->company_address = $data['company_address'] ?? null;

        $this->settings->default_hourly_rate = isset($data['default_hourly_rate']) ? (float) $data['default_hourly_rate'] : null;
        $this->settings->default_daily_rate = isset($data['default_daily_rate']) ? (float) $data['default_daily_rate'] : null;

        if (isset($data['default_daily_reference_hours'])) {
            $this->settings->default_daily_reference_hours = (int) $data['default_daily_reference_hours'];
        }

        if (isset($data['default_rounding_strategy'])) {
            $this->settings->default_rounding_strategy = RoundingStrategy::from($data['default_rounding_strategy']);
        }

        $this->settings->timezone = $data['timezone'] ?? null;
        $this->settings->locale = isset($data['locale']) ? AvailableLocale::from($data['locale']) : null;

        $this->settings->save();

        if ($this->settings->locale !== null) {
            app()->setLocale($this->settings->locale->value);
        }

        if ($this->settings->timezone !== null) {
            config(['app.timezone' => $this->settings->timezone]);
            date_default_timezone_set($this->settings->timezone);
        }
    }
}
