<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Enums\ActivityEventSourceType;
use App\Services\FileWatcherService;

class TestActivitySourceConnection extends Action
{
    public function handle(ActivityEventSourceType $source): bool
    {
        if ($source === ActivityEventSourceType::Fswatch) {
            return FileWatcherService::isAvailable();
        }

        $sourceClass = $source->sourceClass();

        if ($sourceClass === null) {
            return false;
        }

        return app($sourceClass)->isAvailable();
    }
}
