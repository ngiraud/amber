<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'string', 'exists:projects,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
