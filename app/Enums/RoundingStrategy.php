<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

enum RoundingStrategy: int
{
    use EnhanceEnum;

    case Quarter = 15;
    case HalfHour = 30;
    case Hour = 60;

    public function label(): string
    {
        return Str::headline($this->name);
    }

    public function round(int $rawMinutes): int
    {
        if ($rawMinutes <= 0) {
            return 0;
        }

        return (int) ceil($rawMinutes / $this->value) * $this->value;
    }
}
