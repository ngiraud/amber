<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionSource: int
{
    case Manual = 10;
    case Auto = 20;
    case Reconstructed = 30;
}
