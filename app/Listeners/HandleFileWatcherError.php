<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\FileWatcherService;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Events\ChildProcess\ErrorReceived;

class HandleFileWatcherError
{
    public function handle(ErrorReceived $event): void
    {
        if ($event->alias === FileWatcherService::ALIAS) {
            Log::channel('activity')->warning('[fswatch] Error received', ['error' => $event->data]);
        }
    }
}
