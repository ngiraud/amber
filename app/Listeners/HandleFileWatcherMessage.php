<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Activity\RecordActivityEvent;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventType;
use App\Services\FileWatcherService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Events\ChildProcess\MessageReceived;

class HandleFileWatcherMessage
{
    public function __construct(private readonly RecordActivityEvent $recordEvent) {}

    public function handle(MessageReceived $event): void
    {
        if ($event->alias !== FileWatcherService::ALIAS) {
            return;
        }

        $entries = array_values(array_filter(array_map(
            fn (string $line) => $this->parseLine($line),
            explode("\n", mb_trim((string) $event->data)),
        )));

        Log::channel('activity')->info('[fswatch] Watcher', ['entries' => $entries]);

        // deduplicate by file path — keep first occurrence (earliest timestamp)
        $seen = [];
        $entries = array_filter($entries, function (array $entry) use (&$seen): bool {
            [, $filePath] = $entry;
            if (isset($seen[$filePath])) {
                return false;
            }

            return $seen[$filePath] = true;
        });

        Log::channel('activity')->info('[fswatch] Message received', ['count' => count($entries)]);

        foreach ($entries as $entry) {
            [$occurredAt, $filePath] = $entry;

            if (! $this->isAllowedExtension($filePath)) {
                Log::channel('activity')->info('[fswatch] Skipped — extension not allowed', ['file_path' => $filePath]);

                continue;
            }

            $this->recordEvent->handle(new ActivityEventData(
                type: ActivityEventType::FileChange,
                sourceType: 'fswatch',
                occurredAt: $occurredAt,
                metadata: ['file_path' => $filePath],
                filePath: $filePath,
            ));
        }
    }

    /**
     * @return array{CarbonImmutable, string}|null
     */
    private function parseLine(string $line): ?array
    {
        $line = mb_trim($line);

        if ($line === '') {
            return null;
        }

        Log::channel('activity')->info('[fswatch] Line', ['line' => $line]);

        $spacePos = mb_strpos($line, ' ');

        if ($spacePos === false) {
            return null;
        }

        $timestamp = (int) mb_substr($line, 0, $spacePos);
        $filePath = mb_rtrim(mb_substr($line, $spacePos + 1), '~');

        if ($filePath === '' || $timestamp === 0) {
            return null;
        }

        return [CarbonImmutable::createFromTimestamp($timestamp), $filePath];
    }

    private function isAllowedExtension(string $filePath): bool
    {
        $allowed = config('activity.fswatch.allowed_extensions', []);

        if (empty($allowed)) {
            return true;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        return in_array(mb_strtolower($extension), $allowed, strict: true);
    }
}
