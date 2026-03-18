<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Activity\ScanActivitySources;
use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Actions\Session\SwitchSessionProject;
use App\Exceptions\SessionAlreadyActiveException;
use App\Models\Project;
use App\Models\Session;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Native\Desktop\Events\App\OpenedFromURL;
use Native\Desktop\Facades\Notification;
use Native\Desktop\Facades\Window;

class HandleDeepLink
{
    /** @var array<string, string> */
    private const array NAVIGATE_ROUTES = [
        'dashboard' => 'home',
        'timeline' => 'timeline.index',
        'reports' => 'reports.index',
        'clients' => 'clients.index',
        'projects' => 'projects.index',
        'sessions' => 'sessions.index',
        'activity' => 'activity.index',
        'settings' => 'settings.general',
    ];

    public function __construct(
        private readonly StartSession $startSession,
        private readonly StopSession $stopSession,
        private readonly SwitchSessionProject $switchSessionProject,
        private readonly ScanActivitySources $scanActivitySources,
        private readonly ActivitySettings $activitySettings,
    ) {}

    public function handle(OpenedFromURL $event): void
    {
        $parsed = parse_url($event->url);

        $host = $parsed['host'] ?? '';
        $path = mb_ltrim($parsed['path'] ?? '', '/');
        $action = mb_rtrim($host.'/'.($path !== '' ? $path : ''), '/');
        parse_str($parsed['query'] ?? '', $query);

        if (str_starts_with($action, 'navigate/')) {
            $this->navigate(mb_substr($action, 9));

            return;
        }

        match ($action) {
            'session/start' => $this->startSession($query),
            'session/stop' => $this->stopSession(),
            'session/toggle' => $this->toggleSession($query),
            'session/switch' => $this->switchSession($query),
            'activity/sync' => $this->syncActivity(),
            default => null,
        };
    }

    private function navigate(string $page): void
    {
        $routeName = self::NAVIGATE_ROUTES[$page] ?? null;

        if ($routeName === null) {
            return;
        }

        Window::get('main')->url(route($routeName));
    }

    /** @param array<string, string> $query */
    private function startSession(array $query): void
    {
        $project = isset($query['project'])
            ? Project::find($query['project'])
            : Project::active()->first();

        if ($project === null) {
            return;
        }

        try {
            $this->startSession->handle($project);
        } catch (SessionAlreadyActiveException) {
            // Session already running, silently ignore
        }
    }

    private function stopSession(): void
    {
        $active = Session::findActive();

        if ($active === null) {
            return;
        }

        $this->stopSession->handle($active);
    }

    /** @param array<string, string> $query */
    private function toggleSession(array $query): void
    {
        $active = Session::findActive();

        match ($active === null) {
            true => $this->startSession($query),
            false => $this->stopSession(),
        };
    }

    /** @param array<string, string> $query */
    private function switchSession(array $query): void
    {
        if (! isset($query['project'])) {
            return;
        }

        $project = Project::find($query['project']);

        if ($project === null) {
            return;
        }

        $active = Session::findActive();

        if ($active !== null) {
            $this->switchSessionProject->handle($active, $project);

            return;
        }

        try {
            $this->startSession->handle($project);
        } catch (SessionAlreadyActiveException) {
            // Silently ignore
        }
    }

    private function syncActivity(): void
    {
        $since = $this->activitySettings->last_scan_completed_at
            ?? CarbonImmutable::now()->subDays(30);

        Notification::title('Activity Sync')
            ->message('Scanning activity sources…')
            ->show();

        $until = CarbonImmutable::now();

        $result = $this->scanActivitySources->handle($since, $until);

        $this->activitySettings->last_scan_completed_at = $until;
        $this->activitySettings->save();

        $count = $result->count();
        $message = $count > 0
            ? "Recorded {$count} new activity event(s)."
            : 'No new activity events found.';

        Notification::title('Activity Sync Complete')
            ->message($message)
            ->show();
    }
}
