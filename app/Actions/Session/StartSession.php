<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Enums\SessionSource;
use App\Events\SessionStarted;
use App\Exceptions\SessionAlreadyActiveException;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

class StartSession extends Action
{
    public function handle(Project $project, ?string $notes = null): Session
    {
        return DB::transaction(function () use ($project, $notes) {
            $active = Session::findActive();

            if ($active !== null) {
                throw new SessionAlreadyActiveException($active);
            }

            $session = $project->sessions()->create([
                'notes' => $notes,
                'started_at' => now(),
                'source' => SessionSource::Manual,
            ]);

            SessionStarted::dispatch($session);

            return $session;
        });
    }
}
