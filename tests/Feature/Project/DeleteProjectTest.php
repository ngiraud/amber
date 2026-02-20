<?php

declare(strict_types=1);

use App\Actions\Project\DeleteProject;
use App\Models\Client;
use App\Models\Project;

pest()->group('project');

describe('delete project', function () {
    it('delegates to DeleteProject action and redirects to client show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        DeleteProject::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $project->id));

        $this->delete(route('projects.destroy', [$client, $project]))
            ->assertRedirectToRoute('clients.show', $client);
    });

    it('returns 404 for a non-existent project', function () {
        $client = Client::factory()->create();

        $this->delete(route('projects.destroy', [$client, 'non-existent-id']))
            ->assertNotFound();
    });
})->group('controllers');

describe('DeleteProject action', function () {
    it('deletes the project from the database', function () {
        $project = Project::factory()->create();

        DeleteProject::make()->handle($project);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    });
})->group('actions');
