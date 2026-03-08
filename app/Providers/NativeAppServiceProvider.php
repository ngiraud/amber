<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Session;
use App\Services\ApplicationMenuService;
use App\Services\FileWatcherService;
use App\Services\MenuBarService;
use App\Settings\GeneralSettings;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\App;
use Native\Desktop\Facades\System;
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
            ->titleBarHiddenInset()
            ->webPreferences([
                'devTools' => false,
            ])
            ->showDevTools(false)
            ->maximized()
            ->minWidth(1200)
            ->minHeight(600)
            ->rememberState();

        $settings = app(GeneralSettings::class);

        System::theme($settings->theme);
        App::openAtLogin($settings->open_at_login);
        App::badgeCount(Session::hasActive() ? 1 : 0);

        ApplicationMenuService::make()->build();

        MenuBarService::make()->initialize();

        FileWatcherService::make()->start();
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
