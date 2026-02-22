<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;

class StopSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
