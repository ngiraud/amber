<?php

declare(strict_types=1);

use App\Actions\Project\UpdateProject;
use App\Data\ProjectData;
use App\Enums\RoundingStrategy;
use App\Models\Client;
use App\Models\Project;

pest()->group('project');

describe('update project', function () {
    it('delegates to UpdateProject action and redirects to show', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        UpdateProject::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $project->id),
                Mockery::on(fn ($data) => $data->name === 'Updated Project'),
            )
            ->andReturn($project);

        $this->patch(route('projects.update', $project), [
            'client_id' => $client->id,
            'name' => 'Updated Project',
            'color' => '#6366f1',
            'rounding' => RoundingStrategy::Quarter->value,
            'daily_reference_hours' => 7,
            'is_active' => true,
        ])->assertRedirectToRoute('projects.show', $project);
    });

    it('can reassign a project to a different client', function () {
        $oldClient = Client::factory()->create();
        $newClient = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $oldClient->id]);

        $this->patch(route('projects.update', $project), [
            'client_id' => $newClient->id,
            'name' => $project->name,
            'color' => $project->color,
            'rounding' => $project->rounding->value,
            'daily_reference_hours' => $project->daily_reference_hours,
        ])->assertRedirectToRoute('projects.show', $project);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'client_id' => $newClient->id]);
    });

    it('validates required fields', function () {
        $project = Project::factory()->create();

        $this->patch(route('projects.update', $project), [])
            ->assertInvalid(['client_id', 'name', 'color', 'rounding', 'daily_reference_hours']);
    });
})->group('controllers');

describe('UpdateProject action', function () {
    it('updates the project in the database', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['name' => 'Old Name']);
        $data = new ProjectData(
            client: $client,
            name: 'New Name',
            color: '#ff0000',
            rounding: RoundingStrategy::HalfHour,
        );

        UpdateProject::make()->handle($project, $data);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name', 'color' => '#ff0000', 'client_id' => $client->id]);
    });
})->group('actions');
