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
        /** @var ActivityEventSourceType $source */
        $source = $this->route('source');

        return Arr::mapWithKeys($source->configClass()::validationRules(), fn (mixed $rule, string $key) => [
            "{$source->value}.{$key}" => $rule,
        ]);
    }
}
