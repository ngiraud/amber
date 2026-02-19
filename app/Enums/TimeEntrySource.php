<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum TimeEntrySource: int
{
    use EnhanceEnum;

    case Manual = 10;
    case Session = 20;
    case Reconstructed = 30;

    public function label(): string
    {
        return $this->name;
    }
}
