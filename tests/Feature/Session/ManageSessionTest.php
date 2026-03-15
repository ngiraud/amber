<?php

declare(strict_types=1);

use App\Actions\Session\CreateSession;
use App\Actions\Session\DeleteSession;
use App\Actions\Session\UpdateSession;
use App\Data\SessionData;
use App\Enums\RoundingStrategy;
use App\Enums\SessionSource;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('session');

describe('store manual session controller', function () {
    it('delegates to CreateSession and redirects back', function () {
        $project = Project::factory()->create();

        $fake = CreateSession::fake();
        $fake->shouldReceive('manual')->once()->andReturnSelf();
        $fake->shouldReceive('handle')->once()->andReturn(Session::factory()->make());

        $this->post(route('sessions.store'), [
            'project_id' => $project->id,
            'started_at' => '2026-02-26 09:00:00',
            'ended_at' => '2026-02-26 10:00:00',
        ])->assertRedirect();
    });
})->group('controllers');

describe('update session controller', function () {
    it('delegates to UpdateSession and redirects back', function () {
        $session = Session::factory()->create(['ended_at' => now()]);

        UpdateSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($s) => $s->id === $session->id),
                Mockery::type(SessionData::class)
            )
            ->andReturn($session);

        $this->patch(route('sessions.update', $session), [
            'description' => 'Updated description',
        ])->assertRedirect();
    });
})->group('controllers');

describe('destroy session controller', function () {
    it('delegates to DeleteSession and redirects back', function () {
        $session = Session::factory()->create(['ended_at' => now()]);

        DeleteSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id));

        $this->delete(route('sessions.destroy', $session))
            ->assertRedirect();
    });
})->group('controllers');

describe('CreateSession action', function () {
    beforeEach(fn () => Event::fake());

    it('creates a completed session with computed minutes', function () {
        $project = Project::factory()->create(['rounding' => RoundingStrategy::Quarter]);

        $data = new SessionData(
            startedAt: CarbonImmutable::parse('2026-02-26 09:00:00'),
            endedAt: CarbonImmutable::parse('2026-02-26 09:50:00'),
            description: 'Test work',
        );

        $session = CreateSession::make()->manual()->handle($project, $data);

        expect($session->source)->toBe(SessionSource::Manual)
            ->and($session->duration_minutes)->toBe(50)
            ->and($session->rounded_minutes)->toBe(60) // ceil(50/15)*15 = 60
            ->and($session->description)->toBe('Test work')
            ->and($session->date->toDateString())->toBe('2026-02-26')
            ->and($session->is_validated)->toBeTrue();
    });

    it('creates an active session when no endedAt is provided', function () {
        $project = Project::factory()->create();

        $session = CreateSession::make()->manual()->handle($project, new SessionData(notes: 'in progress'));

        expect($session->ended_at)->toBeNull()
            ->and($session->is_validated)->toBeFalse()
            ->and($session->notes)->toBe('in progress');
    });

    it('sets source to Auto', function () {
        $project = Project::factory()->create();

        $data = new SessionData(
            startedAt: CarbonImmutable::parse('2026-02-26 09:00:00'),
            endedAt: CarbonImmutable::parse('2026-02-26 10:00:00'),
        );

        $session = CreateSession::make()->auto()->handle($project, $data);

        expect($session->source)->toBe(SessionSource::Auto);
    });
})->group('actions');

describe('UpdateSession action', function () {
    beforeEach(fn () => Event::fake());

    it('updates times and recalculates minutes', function () {
        $project = Project::factory()->create(['rounding' => RoundingStrategy::Quarter]);
        $session = Session::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-02-26 09:00:00',
            'ended_at' => '2026-02-26 10:00:00',
            'duration_minutes' => 60,
            'rounded_minutes' => 60,
        ]);

        $updated = UpdateSession::make()->handle($session, new SessionData(
            endedAt: CarbonImmutable::parse('2026-02-26 09:45:00'),
        ));

        expect($updated->duration_minutes)->toBe(45)
            ->and($updated->rounded_minutes)->toBe(45);
    });

    it('updates description without changing times', function () {
        $session = Session::factory()->create([
            'ended_at' => now(),
            'description' => 'Old description',
        ]);

        $updated = UpdateSession::make()->handle($session, new SessionData(
            description: 'New description',
        ));

        expect($updated->description)->toBe('New description')
            ->and($updated->duration_minutes)->toBe($session->duration_minutes);
    });

    it('updates notes on an active session', function () {
        $session = Session::factory()->create(['ended_at' => null]);

        $updated = UpdateSession::make()->handle($session, new SessionData(
            notes: 'Working on the dashboard feature',
        ));

        expect($updated->notes)->toBe('Working on the dashboard feature');
    });
})->group('actions');

describe('update session controller with notes', function () {
    it('accepts notes field and delegates to UpdateSession', function () {
        $session = Session::factory()->create(['ended_at' => null]);

        UpdateSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($s) => $s->id === $session->id),
                Mockery::type(SessionData::class)
            )
            ->andReturn($session);

        $this->patch(route('sessions.update', $session), [
            'notes' => 'Working on the dashboard feature',
        ])->assertRedirect();
    });
})->group('controllers');

describe('DeleteSession action', function () {
    it('deletes the session from the database', function () {
        $session = Session::factory()->create(['ended_at' => now()]);

        DeleteSession::make()->handle($session);

        $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
    });

    it('resets activity events where session id was set', function () {
        $session = Session::factory()->create(['ended_at' => now()]);

        ActivityEvent::factory()->count(10)->for($session)->create();

        expect(ActivityEvent::where('session_id', $session->id)->count())->toBe(10);
        expect(ActivityEvent::whereNull('session_id')->count())->toBe(0);

        DeleteSession::make()->handle($session);

        expect(ActivityEvent::whereNull('session_id')->count())->toBe(10);
    });
})->group('actions');
