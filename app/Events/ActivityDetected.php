<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ActivityEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public ActivityEvent $activityEvent) {}
}
