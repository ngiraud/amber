<?php

declare(strict_types=1);

use App\Actions\Session\CheckManualSessionReminder;
use App\Enums\SessionSource;
use App\Events\ManualSessionReminderReached;
use App\Models\Project;
use App\Models\Session;
use App\Settings\ActivitySettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

pest()->group('actions', 'session');

describe('CheckManualSessionReminder action', function () {
    beforeEach(fn () => Event::fake());

    it('dispatches event when reminder interval has elapsed', function () {
        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('put')->once();

        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Manual,
            'started_at' => now()->subMinutes(61),
            'ended_at' => null,
        ]);

        CheckManualSessionReminder::make()->handle();

        Event::assertDispatched(ManualSessionReminderReached::class);
    });

    it('does not dispatch before the reminder interval', function () {
        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Manual,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
        ]);

        CheckManualSessionReminder::make()->handle();

        Event::assertNotDispatched(ManualSessionReminderReached::class);
    });

    it('does not dispatch for Auto sessions', function () {
        $project = Project::factory()->create();
        Session::factory()->auto()->completed()->create([
            'project_id' => $project->id,
            'started_at' => now()->subMinutes(61),
        ]);

        CheckManualSessionReminder::make()->handle();

        Event::assertNotDispatched(ManualSessionReminderReached::class);
    });

    it('does not dispatch when no active session', function () {
        CheckManualSessionReminder::make()->handle();

        Event::assertNotDispatched(ManualSessionReminderReached::class);
    });

    it('does not dispatch duplicate notifications within the same interval', function () {
        Cache::shouldReceive('has')->twice()->andReturn(false, true);
        Cache::shouldReceive('put')->once();

        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Manual,
            'started_at' => now()->subMinutes(61),
            'ended_at' => null,
        ]);

        CheckManualSessionReminder::make()->handle();
        CheckManualSessionReminder::make()->handle();

        Event::assertDispatchedTimes(ManualSessionReminderReached::class, 1);
    });

    it('does not dispatch when reminder is disabled (set to 0)', function () {
        app(ActivitySettings::class)->fill(['manual_session_reminder_minutes' => 0])->save();

        $project = Project::factory()->create();
        Session::factory()->create([
            'project_id' => $project->id,
            'source' => SessionSource::Manual,
            'started_at' => now()->subMinutes(120),
            'ended_at' => null,
        ]);

        CheckManualSessionReminder::make()->handle();

        Event::assertNotDispatched(ManualSessionReminderReached::class);
    });
})->group('actions');
