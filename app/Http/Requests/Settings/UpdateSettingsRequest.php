<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\RoundingStrategy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'git_author_emails' => ['nullable', 'array'],
            'git_author_emails.*' => ['email'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'default_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'default_daily_reference_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'default_rounding_strategy' => ['nullable', Rule::enum(RoundingStrategy::class)],
        ];
    }
}
