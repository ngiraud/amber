<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum ActivityReportStep: string
{
    use EnhanceEnum;

    case CollectingContext = 'collecting_context';
    case BuildingLines = 'building_lines';
    case Summarizing = 'summarizing';
    case GeneratingFiles = 'generating_files';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::CollectingContext => 'Collecting context',
            self::BuildingLines => 'Building lines',
            self::Summarizing => 'Summarizing with AI',
            self::GeneratingFiles => 'Generating files',
            self::Completed => 'Done',
            self::Failed => 'Failed',
        };
    }

    public function shouldDisplayStep(): bool
    {
        return match ($this) {
            self::Completed, self::Failed => false,
            default => true,
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'shouldDisplayStep' => $this->shouldDisplayStep(),
        ];
    }
}
