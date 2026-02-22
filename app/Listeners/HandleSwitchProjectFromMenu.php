<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\SwitchSessionProject;
use App\Events\Native\SwitchProjectFromMenu;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Str;

class HandleSwitchProjectFromMenu
{
    public function __construct(private readonly SwitchSessionProject $switchSessionProject) {}

    public function handle(SwitchProjectFromMenu $event): void
    {
        $projectId = Str::after($event->item['id'] ?? '', 'switch-project:');

        $activeSession = Session::findActive();
        $newProject = Project::find($projectId);

        if ($activeSession === null || $newProject === null || $activeSession->project_id === $newProject->id) {
            return;
        }

        $this->switchSessionProject->handle($activeSession, $newProject);
    }
}
