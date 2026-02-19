<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum SessionSource: int
{
    use EnhanceEnum;

    case Manual = 10;
    case Auto = 20;
    case Reconstructed = 30;

    public function label(): string
    {
        return $this->name;
    }
}
