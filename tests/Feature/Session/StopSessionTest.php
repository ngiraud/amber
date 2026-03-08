<?php

declare(strict_types=1);

use App\Actions\Session\StopSession;
use App\Events\SessionStopped;
use App\Models\Session;
use Illuminate\Support\Facades\Event;

pest()->group('session');

describe('stop session controller', function () {
    it('delegates to StopSession action and redirects to index', function () {
        $session = Session::factory()->create();

        StopSession::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($s) => $s->id === $session->id))
            ->andReturn($session);

        $this->patch(route('sessions.stop', $session))
            ->assertRedirectBack();
    });
})->group('controllers');

describe('StopSession action', function () {
    beforeEach(fn () => Event::fake());

    it('sets ended_at, computes duration_minutes and rounded_minutes', function () {
        $session = Session::factory()->create(['started_at' => now()->subHour()]);
        $session->loadMissing('project');

        $stopped = StopSession::make()->handle($session);

        expect($stopped->ended_at)->not->toBeNull()
            ->and($stopped->duration_minutes)->toBeGreaterThan(0)
            ->and($stopped->rounded_minutes)->toBeGreaterThan(0)
            ->and($stopped->date)->not->toBeNull();

        $this->assertDatabaseHas('sessions', [
            'id' => $session->id,
            'duration_minutes' => $stopped->duration_minutes,
        ]);
    });

    it('dispatches SessionStopped event', function () {
        $session = Session::factory()->create();

        StopSession::make()->handle($session);

        Event::assertDispatched(SessionStopped::class);
    });
})->group('actions');
