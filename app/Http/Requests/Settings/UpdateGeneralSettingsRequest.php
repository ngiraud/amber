<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\AvailableLocale;
use App\Enums\DateFormat;
use App\Enums\RoundingStrategy;
use App\Enums\TimeFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Native\Desktop\Enums\SystemThemesEnum;

class UpdateGeneralSettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'default_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'default_daily_reference_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'default_rounding_strategy' => ['required', Rule::enum(RoundingStrategy::class)],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'locale' => ['required', Rule::enum(AvailableLocale::class)],
            'theme' => ['required', Rule::enum(SystemThemesEnum::class)],
            'open_at_login' => ['required', 'boolean'],
            'date_format' => ['required', Rule::enum(DateFormat::class)],
            'time_format' => ['required', Rule::enum(TimeFormat::class)],
        ];
    }
}
