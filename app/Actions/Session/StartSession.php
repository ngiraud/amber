<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Data\SessionData;
use App\Events\SessionStarted;
use App\Exceptions\SessionAlreadyActiveException;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

class StartSession extends Action
{
    public function __construct(private readonly CreateSession $createSession) {}

    public function handle(Project $project, ?string $notes = null): Session
    {
        return DB::transaction(function () use ($project, $notes) {
            $active = Session::findActive();

            if ($active !== null) {
                throw new SessionAlreadyActiveException($active);
            }

            $session = $this->createSession->auto()->handle($project, new SessionData(notes: $notes));

            SessionStarted::dispatch($session);

            return $session;
        });
    }
}
