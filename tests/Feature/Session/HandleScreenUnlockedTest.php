<?php

declare(strict_types=1);

use App\Listeners\HandleScreenUnlocked;
use App\Services\FileWatcherService;
use Native\Desktop\Events\PowerMonitor\ScreenUnlocked;

use function Pest\Laravel\mock;

pest()->group('listeners', 'session');

describe('HandleScreenUnlocked', function () {
    it('restarts fswatch on screen unlock', function () {
        mock(FileWatcherService::class)
            ->shouldReceive('restart')
            ->once();

        $listener = app(HandleScreenUnlocked::class);
        $listener->handle(new ScreenUnlocked);
    });
});
