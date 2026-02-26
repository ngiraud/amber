<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoundingStrategy;

class TimeEntryService
{
    public function roundMinutesAccordingStrategy(int $rawMinutes, RoundingStrategy $strategy): int
    {
        if ($rawMinutes <= 0) {
            return 0;
        }

        return (int) ceil($rawMinutes / $strategy->value) * $strategy->value;
    }
}
