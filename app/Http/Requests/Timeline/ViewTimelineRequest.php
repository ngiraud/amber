<?php

declare(strict_types=1);

namespace App\Http\Requests\Timeline;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViewTimelineRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', Rule::date()->format('Y')],
            'month' => ['nullable', 'integer', Rule::date()->format('m')],
        ];
    }
}
