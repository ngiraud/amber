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
    beforeEach(function () {
        $this->now = CarbonImmutable::create(2026, 1, 1, 12, 0, 0);
        $this->timeout = config('activity.current_activity_timeout_minutes');
        $this->travelTo($this->now);
    });

    it('returns null when there are no recent events', function () {
        $result = resolveCurrentActivity();

        expect($result)->toBeNull();
    });

    it('returns null when all events are older than current_activity_timeout_minutes', function () {
        $project = Project::factory()->create();

        ActivityEvent::factory()->create([
            'project_id' => $project->id,
            'occurred_at' => $this->now->subMinutes($this->timeout + 1),
        ]);

        $result = resolveCurrentActivity();

        expect($result)->toBeNull();
    });

    it('returns project with since when there are recent events', function () {
        $project = Project::factory()->for(Client::factory())->create();
        $since = $this->now->subMinutes($this->timeout - 1);

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

        $earliest = $this->now->subMinutes($this->timeout - 1);
        $latest = $this->now->subMinutes(1);

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
            'occurred_at' => $this->now->subMinutes(1),
        ]);
        ActivityEvent::factory()->create([
            'project_id' => $projectB->id,
            'occurred_at' => $this->now->subMinutes($this->timeout - 1),
        ]);

        $result = resolveCurrentActivity();

        expect($result)->toHaveCount(2);

        $projectIds = array_column(array_column($result, 'project'), 'id');
        expect($projectIds)->toContain($projectA->id)
            ->and($projectIds)->toContain($projectB->id);
    });
});
