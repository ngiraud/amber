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
            'name' => 'Updated Project',
            'color' => '#6366f1',
            'rounding' => RoundingStrategy::Quarter->value,
            'daily_reference_hours' => 7,
            'is_active' => true,
        ])->assertRedirectToRoute('projects.show', $project);
    });

    it('shows the edit form with project data', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        $this->get(route('projects.edit', $project))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('project/Form')
                ->has('client')
                ->has('project')
            );
    });

    it('validates required fields', function () {
        $project = Project::factory()->create();

        $this->patch(route('projects.update', $project), [])
            ->assertInvalid(['name', 'color', 'rounding', 'daily_reference_hours']);
    });
})->group('controllers');

describe('UpdateProject action', function () {
    it('updates the project in the database', function () {
        $project = Project::factory()->create(['name' => 'Old Name']);
        $data = new ProjectData(
            name: 'New Name',
            color: '#ff0000',
            rounding: RoundingStrategy::HalfHour,
        );

        UpdateProject::make()->handle($project, $data);

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name', 'color' => '#ff0000']);
    });
})->group('actions');
