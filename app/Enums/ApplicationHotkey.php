<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

enum ApplicationHotkey: string
{
    use EnhanceEnum;

    // Navigation
    case NavigateDashboard = 'CmdOrCtrl+1';
    case NavigateTimeline = 'CmdOrCtrl+2';
    case NavigateClients = 'CmdOrCtrl+3';
    case NavigateProjects = 'CmdOrCtrl+4';
    case NavigateReports = 'CmdOrCtrl+5';
    case NavigateSessions = 'CmdOrCtrl+6';
    case NavigateActivity = 'CmdOrCtrl+7';
    case NavigateSettings = 'CmdOrCtrl+,';

    // Session
    case ToggleSession = 'CmdOrCtrl+Shift+S';

    // Creation
    case NewClient = 'CmdOrCtrl+Shift+C';
    case NewProject = 'CmdOrCtrl+Shift+P';

    public function label(): string
    {
        return Str::of($this->name)
            ->remove('Navigate')
            ->headline()
            ->toString();
    }

    public function isNavigation(): bool
    {
        return str_starts_with($this->name, 'Navigate');
    }

    public function navigationUrl(): ?string
    {
        return match ($this) {
            self::NavigateDashboard => route('home'),
            self::NavigateTimeline => route('timeline.index'),
            self::NavigateClients => route('clients.index'),
            self::NavigateProjects => route('projects.index'),
            self::NavigateReports => route('reports.index'),
            self::NavigateSessions => route('sessions.index'),
            self::NavigateActivity => route('activity.index'),
            self::NavigateSettings => route('settings.index'),
            default => null,
        };
    }
}
