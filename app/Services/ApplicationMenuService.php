<?php

declare(strict_types=1);

namespace App\Services;

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

        $startItem = Menu::label('Start Session')
            ->event(OpenStartSessionFromMenu::class)
            ->hotkey('CmdOrCtrl+Shift+S');

        if ($hasActive) {
            $startItem->disabled();
        }

        $stopItem = Menu::label('Stop Session')
            ->event(StopSessionFromMenu::class)
            ->hotkey('CmdOrCtrl+Shift+X');

        if (! $hasActive) {
            $stopItem->disabled();
        }

        Menu::create(
            Menu::app(),
            Menu::edit(),
            Menu::window(),
            Menu::make($startItem, $stopItem)->label('Session'),
        );
    }
}
