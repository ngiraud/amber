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
        return $this->name;
    }
}
