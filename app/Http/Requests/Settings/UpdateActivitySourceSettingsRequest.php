<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivitySourceSettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'git' => ['sometimes', 'array'],
            'git.enabled' => ['boolean'],
            'git.author_emails' => ['array'],
            'git.author_emails.*' => ['email'],

            'github' => ['sometimes', 'array'],
            'github.enabled' => ['boolean'],
            'github.username' => ['nullable', 'string', 'max:255'],

            'claude_code' => ['sometimes', 'array'],
            'claude_code.enabled' => ['boolean'],
            'claude_code.projects_path' => ['string', 'max:500'],

            'fswatch' => ['sometimes', 'array'],
            'fswatch.enabled' => ['boolean'],
            'fswatch.debounce_seconds' => ['integer', 'min:1', 'max:30'],
            'fswatch.excluded_patterns' => ['array'],
            'fswatch.excluded_patterns.*' => ['string'],
            'fswatch.allowed_extensions' => ['array'],
            'fswatch.allowed_extensions.*' => ['string'],
        ];
    }
}
