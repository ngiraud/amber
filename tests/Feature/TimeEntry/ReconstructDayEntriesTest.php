<?php

declare(strict_types=1);

use App\Actions\TimeEntry\ReconstructDayEntries;
use App\Enums\SessionSource;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'time-entry');

describe('ReconstructDayEntries action', function () {
    beforeEach(fn () => Event::fake());

    it('creates sessions from activity event blocks', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        // Two events close together (same block)
        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);
        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 20),
        ]);

        $generated = ReconstructDayEntries::make()->handle($today);

        expect($generated)->toHaveCount(1)
            ->and($generated->first()->source)->toBe(SessionSource::Reconstructed)
            ->and($generated->first()->project_id)->toBe($project->id);
    });

    it('splits events separated by more than idle threshold into separate sessions', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        // 45-minute gap → new block
        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 45),
        ]);

        $generated = ReconstructDayEntries::make()->handle($today);

        expect($generated)->toHaveCount(2);
    });

    it('skips time ranges already covered by an existing session', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        // Existing session covers this period
        Session::factory()->create([
            'project_id' => $project->id,
            'started_at' => $today->setTime(8, 50),
            'ended_at' => $today->setTime(9, 30),
        ]);

        $generated = ReconstructDayEntries::make()->handle($today);

        expect($generated)->toHaveCount(0);
    });

    it('filters by project when provided', function () {
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project1)->create(['occurred_at' => $today->setTime(9, 0)]);
        ActivityEvent::factory()->recycle($project2)->create(['occurred_at' => $today->setTime(10, 0)]);

        $generated = ReconstructDayEntries::make()->handle($today, $project1);

        expect($generated)->toHaveCount(1)
            ->and($generated->first()->project_id)->toBe($project1->id);
    });
});
