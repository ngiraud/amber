<?php

declare(strict_types=1);

namespace App\Http\Requests\Session;

use App\Enums\SessionReconstructMode;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconstructFromDateRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date', 'before:tomorrow'],
            'mode' => ['nullable', Rule::enum(SessionReconstructMode::class)],
        ];
    }

    public function getFromDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('from_date'));
    }

    public function getMode(): SessionReconstructMode
    {
        $mode = $this->validated('mode');

        return $mode !== null ? SessionReconstructMode::from($mode) : SessionReconstructMode::Gaps;
    }
}
