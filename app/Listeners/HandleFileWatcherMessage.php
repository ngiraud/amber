<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Services\FileWatcherService;
use Carbon\CarbonImmutable;
use Native\Desktop\Events\ChildProcess\MessageReceived;

class HandleFileWatcherMessage
{
    public function __construct(private readonly RecordActivityEvent $recordEvent) {}

    public function handle(MessageReceived $event): void
    {
        if ($event->alias !== FileWatcherService::ALIAS) {
            return;
        }

        $filePath = mb_trim((string) $event->data);

        if ($filePath === '') {
            return;
        }

        $this->recordEvent->handle(new ActivityEventData(
            type: ActivityEventType::FileChange,
            sourceType: 'fswatch',
            occurredAt: CarbonImmutable::now(),
            metadata: ['file_path' => $filePath],
            filePath: $filePath,
        ));
    }
}
