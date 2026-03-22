<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\FileWatcherService;
use Native\Desktop\Events\PowerMonitor\ScreenUnlocked;

class HandleScreenUnlocked
{
    public function __construct(private readonly FileWatcherService $fileWatcherService) {}

    public function handle(ScreenUnlocked $event): void
    {
        $this->fileWatcherService->restart();
    }
}
