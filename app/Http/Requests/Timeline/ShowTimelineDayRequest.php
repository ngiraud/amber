<?php

declare(strict_types=1);

namespace App\Http\Requests\Timeline;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowTimelineDayRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function getDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('date'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['date' => $this->route('date')]);
    }
}
