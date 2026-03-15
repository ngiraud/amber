<?php

declare(strict_types=1);

namespace App\Events\Native;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class OpenCreateProjectFromMenu implements ShouldBroadcastNow
{
    public function __construct(
        public readonly array $item = [],
        public readonly array $combo = [],
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
