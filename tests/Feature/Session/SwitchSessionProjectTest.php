<?php

declare(strict_types=1);

use App\Actions\Session\SwitchSessionProject;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\Event;

pest()->group('session');

describe('SwitchSessionProject action', function () {
    beforeEach(fn () => Event::fake());

    it('stops the current session and starts a new one for the new project', function () {
        $currentProject = Project::factory()->create();
        $newProject = Project::factory()->create();
        $currentSession = Session::factory()->create(['project_id' => $currentProject->id]);

        $newSession = SwitchSessionProject::make()->handle($currentSession, $newProject);

        $currentSession->refresh();

        expect($currentSession->ended_at)->not->toBeNull()
            ->and($newSession->project_id)->toBe($newProject->id)
            ->and($newSession->ended_at)->toBeNull();
    });
})->group('actions');
