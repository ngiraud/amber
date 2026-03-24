<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\ActivityEventSourceType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncActivityRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'since' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:since'],
            'source_type' => ['required', Rule::enum(ActivityEventSourceType::class)],
        ];
    }

    public function getSince(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('since'));
    }

    public function getUntil(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('until'));
    }

    public function getSourceType(): ActivityEventSourceType
    {
        return ActivityEventSourceType::from($this->validated('source_type'));
    }
}
