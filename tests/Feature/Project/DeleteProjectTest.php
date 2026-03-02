<?php

declare(strict_types=1);

use App\Actions\Project\DeleteProject;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;

pest()->group('project');

describe('delete project', function () {
    it('delegates to DeleteProject action and redirects to client show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        DeleteProject::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $project->id));

        $this->delete(route('projects.destroy', $project))
            ->assertRedirectToRoute('clients.show', $client);
    });

    it('returns 404 for a non-existent project', function () {
        $this->delete(route('projects.destroy', 'non-existent-id'))
            ->assertNotFound();
    });
})->group('controllers');

describe('DeleteProject action', function () {
    it('deletes the project from the database', function () {
        $project = Project::factory()->create();

        DeleteProject::make()->handle($project);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    });

    it('also deletes associated repositories, sessions, and activity events', function () {
        $this->withoutDefer();

        $project = Project::factory()->create();
        $repository = ProjectRepository::factory()->create(['project_id' => $project->id]);
        $session = Session::factory()->create(['project_id' => $project->id]);
        $event = ActivityEvent::factory()->create(['project_id' => $project->id, 'project_repository_id' => $repository->id]);

        DeleteProject::make()->handle($project);

        $this->assertDatabaseMissing('project_repositories', ['id' => $repository->id]);
        $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('activity_events', ['id' => $event->id]);
    });
})->group('actions');
