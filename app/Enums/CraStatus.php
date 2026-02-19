<?php

declare(strict_types=1);

namespace App\Enums;

enum CraStatus: int
{
    case Draft = 10;
    case Finalized = 20;
    case Sent = 30;
}
