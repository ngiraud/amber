<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructSessionsFromDate;
use App\Enums\SessionReconstructMode;
use App\Enums\SessionSource;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'session');

describe('ReconstructSessionsFromDate action', function () {
    beforeEach(fn () => Event::fake());

    it('reconstructs sessions across multiple days', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();
        $twoDaysAgo = $today->subDays(2);

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $twoDaysAgo->setTime(9, 0),
        ]);
        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(10, 0),
        ]);

        $generated = ReconstructSessionsFromDate::make()->handle($twoDaysAgo);

        expect($generated)->toHaveCount(2);
    });

    it('stops at today and does not go into the future', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        $generated = ReconstructSessionsFromDate::make()->handle($today);

        expect($generated)->toHaveCount(1);
    });

    it('replaces auto sessions across all days when replaceAuto is true', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $yesterday->setTime(9, 0),
        ]);

        $oldSession = Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Auto,
            'started_at' => $yesterday->setTime(8, 50),
            'ended_at' => $yesterday->setTime(9, 30),
            'date' => $yesterday->toDateString(),
        ]);

        ReconstructSessionsFromDate::make()->handle($yesterday, mode: SessionReconstructMode::Replace);

        expect(Session::find($oldSession->id))->toBeNull();
    });

    it('returns an empty collection when no events exist in the date range', function () {
        $today = CarbonImmutable::today();

        $generated = ReconstructSessionsFromDate::make()->handle($today->subDays(3));

        expect($generated)->toBeEmpty();
    });
});
