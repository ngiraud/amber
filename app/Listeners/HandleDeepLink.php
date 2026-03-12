<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Exceptions\SessionAlreadyActiveException;
use App\Models\Project;
use App\Models\Session;
use Native\Desktop\Events\App\OpenedFromURL;

class HandleDeepLink
{
    public function __construct(
        private readonly StartSession $startSession,
        private readonly StopSession $stopSession,
    ) {}

    public function handle(OpenedFromURL $event): void
    {
        $parsed = parse_url($event->url);

        $host = $parsed['host'] ?? '';
        $path = mb_ltrim($parsed['path'] ?? '', '/');
        $action = $host.'/'.($path !== '' ? $path : '');
        parse_str($parsed['query'] ?? '', $query);

        match (mb_rtrim($action, '/')) {
            'session/start' => $this->startSession($query),
            'session/stop' => $this->stopSession(),
            default => null,
        };
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
}
