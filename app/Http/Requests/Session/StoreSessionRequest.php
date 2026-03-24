<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'string', Rule::exists(Project::class, 'id')],
            'started_at' => ['sometimes', 'date'],
            'ended_at' => ['sometimes', 'date', 'after:started_at'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
