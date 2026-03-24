<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use App\Enums\SessionReconstructMode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconstructDailySessionsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'before:tomorrow'],
            'mode' => [Rule::enum(SessionReconstructMode::class)],
        ];
    }

    public function getDate(): CarbonImmutable
    {
        $date = $this->validated('date');

        if (is_null($date)) {
            return CarbonImmutable::today();
        }

        return CarbonImmutable::parse($date);
    }

    public function getMode(): SessionReconstructMode
    {
        $mode = $this->validated('mode');

        return $mode !== null ? SessionReconstructMode::from($mode) : SessionReconstructMode::Gaps;
    }
}
