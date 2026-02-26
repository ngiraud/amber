<?php

declare(strict_types=1);

namespace App\Actions\TimeEntry;

use App\Actions\Action;
use App\Enums\RoundingStrategy;

class RoundMinutes extends Action
{
    public function handle(int $rawMinutes, RoundingStrategy $strategy): int
    {
        if ($rawMinutes <= 0) {
            return 0;
        }

        return (int) ceil($rawMinutes / $strategy->value) * $strategy->value;
    }
}
