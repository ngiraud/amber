<?php

declare(strict_types=1);

use App\Actions\Client\DeleteClient;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;

pest()->group('client');

describe('delete client', function () {
    it('delegates to DeleteClient action and redirects to index', function () {
        $client = Client::factory()->create();

        DeleteClient::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $client->id));

        $this->delete(route('clients.destroy', $client))
            ->assertRedirectToRoute('clients.index');
    });

    it('returns 404 for a non-existent client', function () {
        $this->delete(route('clients.destroy', 'non-existent-id'))
            ->assertNotFound();
    });
})->group('controllers');

describe('DeleteClient action', function () {
    it('deletes the client and all its projects, repositories, sessions, and activity events', function () {
        $this->withoutDefer();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $repository = ProjectRepository::factory()->create(['project_id' => $project->id]);
        $session = Session::factory()->create(['project_id' => $project->id]);
        $event = ActivityEvent::factory()->create(['project_id' => $project->id, 'project_repository_id' => $repository->id]);

        DeleteClient::make()->handle($client);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('project_repositories', ['id' => $repository->id]);
        $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('activity_events', ['id' => $event->id]);
    });
})->group('actions');
