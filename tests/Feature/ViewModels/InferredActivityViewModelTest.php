<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\ViewModels\CurrentActivityViewModel;
use Carbon\CarbonImmutable;
use Inertia\RenderContext;

pest()->group('viewmodels');

function resolveCurrentActivity(): mixed
{
    $viewModel = new CurrentActivityViewModel;
    $context = Mockery::mock(RenderContext::class);
    $props = $viewModel->toInertiaProperties($context);

    return value($props['currentActivity']);
}

describe('CurrentActivityViewModel', function () {
    it('returns null when there are no recent events', function () {
        $result = resolveCurrentActivity();

        expect($result)->toBeNull();
    });

    it('returns null when all events are older than current_activity_timeout_minutes', function () {
        $project = Project::factory()->create();

        ActivityEvent::factory()->create([
            'project_id' => $project->id,
            'occurred_at' => CarbonImmutable::now()->subMinutes(11),
        ]);

        $result = resolveCurrentActivity();

        expect($result)->toBeNull();
    });

    it('returns project with since when there are recent events', function () {
        $project = Project::factory()->for(Client::factory())->create();
        $since = CarbonImmutable::now()->subMinutes(5);

        ActivityEvent::factory()->create([
            'project_id' => $project->id,
            'occurred_at' => $since,
        ]);

        $result = resolveCurrentActivity();

        expect($result)->toHaveCount(1)
            ->and($result[0]['project']['id'])->toBe($project->id)
            ->and($result[0]['since'])->toBe($since->toIso8601String());
    });

    it('returns the earliest event occurrence as since within the window', function () {
        $project = Project::factory()->create();

        $earliest = CarbonImmutable::now()->subMinutes(8);
        $latest = CarbonImmutable::now()->subMinutes(2);

        ActivityEvent::factory()->create(['project_id' => $project->id, 'occurred_at' => $latest]);
        ActivityEvent::factory()->create(['project_id' => $project->id, 'occurred_at' => $earliest]);

        $result = resolveCurrentActivity();

        expect($result)->toHaveCount(1)
            ->and($result[0]['since'])->toBe($earliest->toIso8601String());
    });

    it('returns multiple entries when events exist for several projects', function () {
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        ActivityEvent::factory()->create([
            'project_id' => $projectA->id,
            'occurred_at' => CarbonImmutable::now()->subMinutes(3),
        ]);
        ActivityEvent::factory()->create([
            'project_id' => $projectB->id,
            'occurred_at' => CarbonImmutable::now()->subMinutes(7),
        ]);

        $result = resolveCurrentActivity();

        expect($result)->toHaveCount(2);

        $projectIds = array_column(array_column($result, 'project'), 'id');
        expect($projectIds)->toContain($projectA->id)
            ->and($projectIds)->toContain($projectB->id);
    });
});
