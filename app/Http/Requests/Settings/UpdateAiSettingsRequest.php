<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\AiProvider;
use App\Enums\AvailableLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'provider' => ['required', 'nullable', Rule::enum(AiProvider::class)],
            'api_key' => [Rule::requiredIf(fn () => $this->enum('provider', AiProvider::class)?->requiresApiKey()), 'string', 'max:500'],
            'summary_language' => ['required', 'nullable', 'string', Rule::enum(AvailableLocale::class)],
        ];
    }
}
