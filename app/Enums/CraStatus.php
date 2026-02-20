<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum CraStatus: int
{
    use EnhanceEnum;

    case Draft = 10;
    case Finalized = 20;
    case Sent = 30;

    public function label(): string
    {
        return $this->name;
    }
}
