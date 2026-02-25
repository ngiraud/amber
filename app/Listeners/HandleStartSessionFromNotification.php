<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Session\StartSession;
use App\Models\Project;
use Native\Desktop\Events\Notifications\NotificationActionClicked;

class HandleStartSessionFromNotification
{
    public function __construct(private readonly StartSession $startSession) {}

    public function handle(NotificationActionClicked $event): void
    {
        if ($event->index !== 0) {
            return;
        }

        $project = Project::find($event->reference);

        if ($project === null) {
            return;
        }

        $this->startSession->handle($project);
    }
}
