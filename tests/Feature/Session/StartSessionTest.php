<?php

declare(strict_types=1);

use App\Actions\Session\StartSession;
use App\Enums\SessionSource;
use App\Events\SessionAlreadyActiveAttempted;
use App\Events\SessionStarted;
use App\Exceptions\SessionAlreadyActiveException;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\Event;

pest()->group('session');

describe('start session controller', function () {
    it('delegates to StartSession action and redirects to index', function () {
        $project = Project::factory()->create();

        StartSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($p) => $p->id === $project->id),
                null,
            )
            ->andReturn(Session::factory()->make());

        $this->post(route('sessions.start'), ['project_id' => $project->id])
            ->assertRedirectToRoute('sessions.index');
    });

    it('validates that project_id is required', function () {
        $this->post(route('sessions.start'), [])
            ->assertInvalid(['project_id']);
    });

    it('validates that project_id must exist', function () {
        $this->post(route('sessions.start'), ['project_id' => 'nonexistent'])
            ->assertInvalid(['project_id']);
    });

    it('flashes an error and dispatches SessionAlreadyActiveAttempted when a session is already active', function () {
        Event::fake();

        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id]);

        $this->post(route('sessions.start'), ['project_id' => $project->id])
            ->assertRedirect()
            ->assertInertiaFlash('error', 'A session is already active.');

        Event::assertDispatched(SessionAlreadyActiveAttempted::class);
    });
})->group('controllers');

describe('StartSession action', function () {
    beforeEach(fn () => Event::fake());

    it('creates a session in the database', function () {
        $project = Project::factory()->create();

        $session = StartSession::make()->handle($project);

        expect($session)->toBeInstanceOf(Session::class)
            ->and($session->project_id)->toBe($project->id)
            ->and($session->source)->toBe(SessionSource::Manual)
            ->and($session->ended_at)->toBeNull();

        $this->assertDatabaseHas('sessions', ['project_id' => $project->id, 'ended_at' => null]);
    });

    it('dispatches SessionStarted event', function () {
        $project = Project::factory()->create();

        StartSession::make()->handle($project);

        Event::assertDispatched(SessionStarted::class);
    });

    it('throws SessionAlreadyActiveException when a session is already running', function () {
        $project = Project::factory()->create();
        Session::factory()->create(['project_id' => $project->id]);

        expect(fn () => StartSession::make()->handle($project))
            ->toThrow(SessionAlreadyActiveException::class);
    });
})->group('actions');
