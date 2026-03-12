<?php

declare(strict_types=1);

use App\Actions\Project\ToggleProjectStatus;
use App\Models\Project;

pest()->group('project');

describe('toggle project status', function () {
    it('delegates to ToggleProjectStatus action and redirects back', function () {
        $project = Project::factory()->create(['is_active' => true]);

        ToggleProjectStatus::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($p) => $p->id === $project->id))
            ->andReturn($project->fresh());

        $this->post(route('projects.toggle-status', $project))
            ->assertRedirect();
    });
})->group('controllers');

describe('ToggleProjectStatus action', function () {
    it('archives an active project', function () {
        $project = Project::factory()->create(['is_active' => true]);

        $updated = ToggleProjectStatus::make()->handle($project);

        expect($updated->is_active)->toBeFalse();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_active' => false]);
    });

    it('restores an archived project', function () {
        $project = Project::factory()->create(['is_active' => false]);

        $updated = ToggleProjectStatus::make()->handle($project);

        expect($updated->is_active)->toBeTrue();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'is_active' => true]);
    });
})->group('actions');
