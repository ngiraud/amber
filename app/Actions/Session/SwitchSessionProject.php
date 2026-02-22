<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

class SwitchSessionProject extends Action
{
    public function __construct(
        protected StartSession $startSession,
        protected StopSession $stopSession,
    ) {}

    public function handle(Session $currentSession, Project $newProject): Session
    {
        return DB::transaction(function () use ($currentSession, $newProject) {
            $this->stopSession->handle($currentSession);

            return $this->startSession->handle($newProject);
        });
    }
}
