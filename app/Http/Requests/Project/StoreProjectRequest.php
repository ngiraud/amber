<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Enums\RoundingStrategy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'size:7', 'starts_with:#'],
            'rounding' => ['required', Rule::enum(RoundingStrategy::class)],
            'daily_reference_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'is_active' => ['nullable', 'boolean'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
