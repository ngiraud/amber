<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructDailySessions;
use App\Enums\SessionReconstructMode;
use App\Enums\SessionSource;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'session');

describe('ReconstructDailySessions replace mode', function () {
    beforeEach(fn () => Event::fake());

    it('deletes existing auto sessions before reconstructing when replaceAuto is true', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        $event = ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        $existingSession = Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Auto,
            'started_at' => $today->setTime(8, 50),
            'ended_at' => $today->setTime(9, 30),
            'date' => $today->toDateString(),
        ]);

        $event->update(['session_id' => $existingSession->id]);

        $generated = ReconstructDailySessions::make()->handle($today, null, mode: SessionReconstructMode::Replace);

        expect(Session::find($existingSession->id))->toBeNull()
            ->and($generated)->toHaveCount(1)
            ->and($generated->first()->source)->toBe(SessionSource::Auto);
    });

    it('re-links activity events to the newly reconstructed session', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        $event = ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        $existingSession = Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Auto,
            'started_at' => $today->setTime(8, 50),
            'ended_at' => $today->setTime(9, 30),
            'date' => $today->toDateString(),
        ]);

        $event->update(['session_id' => $existingSession->id]);

        $generated = ReconstructDailySessions::make()->handle($today, null, mode: SessionReconstructMode::Replace);

        expect($event->fresh()->session_id)->toBe($generated->first()->id);
    });

    it('preserves manual sessions when replaceAuto is true', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(14, 0),
        ]);

        $manualSession = Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Manual,
            'started_at' => $today->setTime(9, 0),
            'ended_at' => $today->setTime(10, 0),
            'date' => $today->toDateString(),
        ]);

        ReconstructDailySessions::make()->handle($today, null, mode: SessionReconstructMode::Replace);

        expect(Session::find($manualSession->id))->not->toBeNull();
    });

    it('does not delete auto sessions when replaceAuto is false', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        ActivityEvent::factory()->recycle($project)->create([
            'occurred_at' => $today->setTime(9, 0),
        ]);

        $existingSession = Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Auto,
            'started_at' => $today->setTime(8, 50),
            'ended_at' => $today->setTime(9, 30),
            'date' => $today->toDateString(),
        ]);

        ReconstructDailySessions::make()->handle($today, null, mode: SessionReconstructMode::Gaps);

        expect(Session::find($existingSession->id))->not->toBeNull();
    });
});
