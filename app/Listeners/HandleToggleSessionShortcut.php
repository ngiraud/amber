<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Events\Native\ToggleSessionShortcut;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Native\Desktop\Facades\Notification;

class HandleToggleSessionShortcut
{
    public function __construct(
        private readonly StopSession $stopSession,
        private readonly StartSession $startSession,
    ) {}

    public function handle(ToggleSessionShortcut $event): void
    {
        $active = Session::findActive();

        if ($active !== null) {
            $this->stopSession->handle($active);

            return;
        }

        $lastProject = Project::active()
            ->whereHas('sessions', fn (Builder $q) => $q->orderByDesc('started_at'))
            ->first();

        if ($lastProject === null) {
            Notification::title('CRA Tracker')
                ->message('No active project found. Please create a project first.')
                ->show();

            return;
        }

        $this->startSession->handle($lastProject);
    }
}
