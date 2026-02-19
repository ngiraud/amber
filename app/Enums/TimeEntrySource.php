<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeEntrySource: int
{
    case Manual = 10;
    case Session = 20;
    case Reconstructed = 30;
}
