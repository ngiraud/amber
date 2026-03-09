<?php

declare(strict_types=1);

namespace App\Http\Requests\ActivityReport;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateActivityReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'use_ai_summary' => ['boolean'],
        ];
    }
}
