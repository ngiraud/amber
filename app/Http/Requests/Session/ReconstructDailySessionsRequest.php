<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class ReconstructDailySessionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'before:tomorrow'],
        ];
    }

    public function getDate(): CarbonImmutable
    {
        $date = $this->validated('date');

        if (is_null($date)) {
            return CarbonImmutable::today();
        }

        return CarbonImmutable::parse($date);
    }
}
