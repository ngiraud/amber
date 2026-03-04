<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivitySettingsRequest extends FormRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idle_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'untracked_threshold_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'scan_interval_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'block_end_padding_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }
}
