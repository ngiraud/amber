<?php

declare(strict_types=1);

namespace App\Enums;

enum RoundingStrategy: int
{
    case Quarter = 15;
    case HalfHour = 30;
    case Hour = 60;
}
