<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\ActivityEventSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class UpdateActivitySourceSettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ActivityEventSourceType::collect()
            ->flatMap(
                fn (ActivityEventSourceType $type) => Arr::mapWithKeys($type->configClass()::validationRules(), fn ($rule, $key) => [
                    "{$type->value}.{$key}" => $rule,
                ])
            )
            ->all();
    }
}
