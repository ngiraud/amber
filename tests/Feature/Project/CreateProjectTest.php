<?php

declare(strict_types=1);

use App\Actions\Project\CreateProject;
use App\Data\ProjectData;
use App\Enums\RoundingStrategy;
use App\Models\Client;
use App\Models\Project;

pest()->group('project');

describe('create project', function () {
    it('delegates to CreateProject action and redirects to show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->make(['id' => 'fake-id', 'client_id' => $client->id]);

        CreateProject::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data->client->id === $client->id && $data->name === 'New Project'))
            ->andReturn($project);

        $this->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'New Project',
            'color' => '#6366f1',
            'rounding' => RoundingStrategy::Quarter->value,
            'daily_reference_hours' => 7,
            'is_active' => true,
        ])->assertRedirectToRoute('projects.show', $project);
    });

    it('validates required fields', function () {
        $this->post(route('projects.store'), [])
            ->assertInvalid(['client_id', 'name', 'color', 'rounding', 'daily_reference_hours']);
    });

    it('validates client_id must exist', function () {
        $this->post(route('projects.store'), [
            'client_id' => 'non-existent-id',
            'name' => 'Test',
            'color' => '#6366f1',
            'rounding' => RoundingStrategy::Quarter->value,
            'daily_reference_hours' => 7,
        ])->assertInvalid(['client_id']);
    });

    it('validates color format', function () {
        $client = Client::factory()->create();

        $this->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'Test',
            'color' => 'not-a-color',
            'rounding' => RoundingStrategy::Quarter->value,
            'daily_reference_hours' => 7,
        ])->assertInvalid(['color']);
    });

    it('validates rounding is a valid enum value', function () {
        $client = Client::factory()->create();

        $this->post(route('projects.store'), [
            'client_id' => $client->id,
            'name' => 'Test',
            'color' => '#6366f1',
            'rounding' => 999,
            'daily_reference_hours' => 7,
        ])->assertInvalid(['rounding']);
    });
})->group('controllers');

describe('CreateProject action', function () {
    it('creates a project under the client', function () {
        $client = Client::factory()->create();
        $data = new ProjectData(
            client: $client,
            name: 'My Project',
            color: '#6366f1',
            rounding: RoundingStrategy::Quarter,
        );

        $project = CreateProject::make()->handle($data);

        expect($project)->toBeInstanceOf(Project::class)
            ->and($project->client_id)->toBe($client->id)
            ->and($project->name)->toBe('My Project');

        $this->assertDatabaseHas('projects', ['name' => 'My Project', 'client_id' => $client->id]);
    });
})->group('actions');
