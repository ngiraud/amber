<?php

declare(strict_types=1);

namespace App\Events\Native;

use App\Enums\ApplicationHotkey;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NavigateToPage implements ShouldBroadcastNow
{
    public readonly ?string $url;

    public function __construct(
        public readonly array $item = [],
        public readonly array $combo = [],
    ) {
        $this->url = ApplicationHotkey::collect()
            ->filter(fn (ApplicationHotkey $h) => $h->isNavigation())
            ->first(fn (ApplicationHotkey $h) => $h->label() === ($item['label'] ?? ''))
            ?->navigationUrl();
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
