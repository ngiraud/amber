<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ActivityReportStep;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ActivityReportProgress implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $reportId,
        public readonly ActivityReportStep $step,
        public readonly ?string $message = null,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
