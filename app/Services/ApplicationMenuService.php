<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationHotkey;
use App\Events\Native\NavigateToPage;
use App\Events\Native\OpenCreateClientFromMenu;
use App\Events\Native\OpenCreateProjectFromMenu;
use App\Events\Native\OpenStartSessionFromMenu;
use App\Events\Native\StopSessionFromMenu;
use App\Models\Session;
use Native\Desktop\Facades\Menu;

class ApplicationMenuService
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function build(): void
    {
        $hasActive = Session::hasActive();

        $sessionItem = $hasActive
            ? Menu::label('Stop Session')->event(StopSessionFromMenu::class)->hotkey(ApplicationHotkey::ToggleSession->value)
            : Menu::label('Start Session')->event(OpenStartSessionFromMenu::class)->hotkey(ApplicationHotkey::ToggleSession->value);

        $navigateItems = ApplicationHotkey::collect()
            ->filter(fn (ApplicationHotkey $h) => $h->isNavigation())
            ->map(fn (ApplicationHotkey $h) => Menu::label($h->label())
                ->event(NavigateToPage::class)
                ->hotkey($h->value)
            )
            ->values()
            ->all();

        $newClientItem = Menu::label('New Client')
            ->event(OpenCreateClientFromMenu::class)
            ->hotkey(ApplicationHotkey::NewClient->value);

        $newProjectItem = Menu::label('New Project')
            ->event(OpenCreateProjectFromMenu::class)
            ->hotkey(ApplicationHotkey::NewProject->value);

        Menu::create(
            Menu::app(),
            Menu::edit(),
            Menu::window(),
            Menu::make($sessionItem)->label('Session'),
            Menu::make(...$navigateItems)->label('Navigate'),
            Menu::make($newClientItem, $newProjectItem)->label('New'),
        );
    }
}
