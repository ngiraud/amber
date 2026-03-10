<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum ActivityReportStatus: int
{
    use EnhanceEnum;

    case Draft = 10;
    case Generating = 20;
    case Failed = 25;
    case Finalized = 30;
    case Sent = 40;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Generating => 'Generating',
            self::Failed => 'Failed',
            self::Finalized => 'Finalized',
            self::Sent => 'Sent',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Generating => 'outline',
            self::Draft => 'secondary',
            self::Failed => 'destructive',
            default => 'default',
        };
    }

    public function shouldDisplayBadge(): bool
    {
        return $this !== self::Finalized;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'variant' => $this->variant(),
            'shouldDisplayBadge' => $this->shouldDisplayBadge(),
        ];
    }
}
