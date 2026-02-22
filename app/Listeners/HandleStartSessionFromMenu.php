<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StartSession;
use App\Events\Native\StartSessionFromMenu;
use App\Models\Project;
use Illuminate\Support\Str;

class HandleStartSessionFromMenu
{
    public function __construct(private readonly StartSession $startSession) {}

    public function handle(StartSessionFromMenu $event): void
    {
        $projectId = Str::after($event->item['id'] ?? '', 'start-session:');

        $project = Project::find($projectId);

        if ($project === null) {
            return;
        }

        $this->startSession->handle($project);
    }
}
