<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Native\StartSessionFromMenu;
use App\Events\Native\StopSessionFromMenu;
use App\Events\Native\SwitchProjectFromMenu;
use App\Models\Project;
use App\Models\Session;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;

class MenuBarService
{
    public static function make(): self
    {
        return app(self::class);
    }

    public function initialize(): void
    {
        $active = Session::findActive(['project']);

        MenuBar::create()
            ->showDockIcon()
            ->showOnAllWorkspaces()
            ->icon($this->resolveIcon($active))
            ->label($active !== null ? $this->formatElapsed($active) : '')
            ->tooltip('Activity Tracker')
            ->onlyShowContextMenu()
            ->withContextMenu(
                $active !== null ? $this->buildActiveMenu($active) : $this->buildIdleMenu()
            );
    }

    public function refresh(): void
    {
        $active = Session::findActive(['project']);

        MenuBar::icon($this->resolveIcon($active));
        MenuBar::label($active !== null ? $this->formatElapsed($active) : '');
        MenuBar::contextMenu(
            $active !== null ? $this->buildActiveMenu($active) : $this->buildIdleMenu()
        );
    }

    public function updateLabel(): void
    {
        $active = Session::findActive();

        if ($active !== null) {
            MenuBar::label($this->formatElapsed($active));
        }
    }

    public function buildIdleMenu(): \Native\Desktop\Menu\Menu
    {
        $projects = Project::active()
            ->with('client:id,name')
            ->get()
            ->map(fn (Project $project) => Menu::label("{$project->client->name} — {$project->name}")
                ->id("start-session:{$project->id}")
                ->event(StartSessionFromMenu::class)
            )
            ->all();

        $items = [
            count($projects) > 0 ? Menu::make(...$projects)->label('Start Session') : Menu::label('No active projects')->disabled(),
            Menu::separator(),
            Menu::route('home', 'Open App'),
            Menu::separator(),
            Menu::quit(),
        ];

        return Menu::make(...$items);
    }

    public function buildActiveMenu(Session $session): \Native\Desktop\Menu\Menu
    {
        $projects = Project::active()
            ->with('client:id,name')
            ->where('id', '!=', $session->project_id)
            ->get()
            ->map(fn (Project $project) => Menu::label("{$project->client->name} — {$project->name}")
                ->id("switch-project:{$project->id}")
                ->event(SwitchProjectFromMenu::class)
            )
            ->all();

        $items = array_filter([
            Menu::label($session->project->name)->disabled(),
            Menu::label($this->formatElapsed($session))->disabled(),
            Menu::separator(),
            Menu::label('Stop Session')->event(StopSessionFromMenu::class),
            count($projects) > 0 ? Menu::make(...$projects)->label('Switch Project') : null,
            Menu::separator(),
            Menu::route('home', 'Open App'),
            Menu::separator(),
            Menu::quit(),
        ]);

        return Menu::make(...$items);
    }

    public function resolveIcon(?Session $session): string
    {
        return $session !== null
            ? public_path('menubarActiveTemplate.png')
            : public_path('menubarIdleTemplate.png');
    }

    public function formatElapsed(Session $session): string
    {
        $totalMinutes = (int) $session->started_at->diffInMinutes(now());
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
