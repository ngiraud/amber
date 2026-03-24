<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['sometimes', 'date'],
            'ended_at' => ['sometimes', 'date', 'after:started_at'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
