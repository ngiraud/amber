<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\FileWatcherService;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Events\ChildProcess\ProcessExited;

class HandleFileWatcherExit
{
    public function handle(ProcessExited $event): void
    {
        if ($event->alias === FileWatcherService::ALIAS) {
            Log::channel('activity')->warning('[fswatch] Process exited', ['code' => $event->code]);
        }
    }
}
