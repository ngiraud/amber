<?php

declare(strict_types=1);

use App\Actions\Project\AttachRepository;
use App\Actions\Project\DetachRepository;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRepository;

pest()->group('project');

describe('attach repository', function () {
    it('delegates to AttachRepository action and redirects to project show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $repository = ProjectRepository::factory()->make(['project_id' => $project->id]);

        AttachRepository::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $project->id),
                '/Users/nico/code/my-repo',
                'my-repo',
            )
            ->andReturn($repository);

        $this->post(route('projects.repositories.store', $project), [
            'local_path' => '/Users/nico/code/my-repo',
            'name' => 'my-repo',
        ])->assertRedirectToRoute('projects.show', $project);
    });

    it('validates repository fields are required', function () {
        $project = Project::factory()->create();

        $this->post(route('projects.repositories.store', $project), [])
            ->assertInvalid(['local_path', 'name']);
    });
})->group('controllers');

describe('detach repository', function () {
    it('delegates to DetachRepository action and redirects to project show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $repository = ProjectRepository::factory()->create(['project_id' => $project->id]);

        DetachRepository::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $repository->id));

        $this->delete(route('projects.repositories.destroy', [$project, $repository]))
            ->assertRedirectToRoute('projects.show', $project);
    });

    it('returns 404 for a non-existent repository', function () {
        $project = Project::factory()->create();

        $this->delete(route('projects.repositories.destroy', [$project, 'non-existent-id']))
            ->assertNotFound();
    });
})->group('controllers');

describe('AttachRepository action', function () {
    it('creates a repository record for the project', function () {
        $project = Project::factory()->create();

        $repository = AttachRepository::make()->handle($project, '/Users/nico/code/my-repo', 'my-repo');

        expect($repository)->toBeInstanceOf(ProjectRepository::class)
            ->and($repository->project_id)->toBe($project->id)
            ->and($repository->local_path)->toBe('/Users/nico/code/my-repo');

        $this->assertDatabaseHas('project_repositories', ['project_id' => $project->id, 'name' => 'my-repo']);
    });
})->group('actions');

describe('DetachRepository action', function () {
    it('deletes the repository from the database', function () {
        $repository = ProjectRepository::factory()->create();

        DetachRepository::make()->handle($repository);

        $this->assertDatabaseMissing('project_repositories', ['id' => $repository->id]);
    });

    it('also deletes associated activity events', function () {
        $this->withoutDefer();

        $repository = ProjectRepository::factory()->create();
        $event = ActivityEvent::factory()->create([
            'project_id' => $repository->project_id,
            'project_repository_id' => $repository->id,
        ]);

        DetachRepository::make()->handle($repository);

        $this->assertDatabaseMissing('activity_events', ['id' => $event->id]);
    });
})->group('actions');
