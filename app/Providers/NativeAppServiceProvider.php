<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Native\ToggleSessionShortcut;
use App\Services\FileWatcherService;
use App\Services\MenuBarService;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\GlobalShortcut;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open()
            ->width(1200)
            ->height(600);

        MenuBarService::make()->initialize();

        GlobalShortcut::key('CmdOrCtrl+Shift+T')
            ->event(ToggleSessionShortcut::class)
            ->register();

        if (config('activity.fswatch.enabled')) {
            FileWatcherService::make()->start();
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
