<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProjectRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'array'],
            'client_id.*' => ['nullable', Rule::exists(Client::class, 'id')],
        ];
    }
}
