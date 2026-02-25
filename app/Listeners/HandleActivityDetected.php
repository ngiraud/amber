<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ActivityDetected;

class HandleActivityDetected
{
    public function handle(ActivityDetected $event): void {}
}
