<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructDailySessions;
use App\Actions\Session\ReconstructSessionsFromDate;
use App\Enums\SessionReconstructMode;
use App\Models\Session;

pest()->group('controllers', 'session');

describe('reconstruct controller', function () {
    it('delegates to ReconstructDailySessions and redirects', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([Session::factory()->make()]));

        $this->post(route('sessions.reconstruct'), ['mode' => SessionReconstructMode::Gaps->value])
            ->assertRedirect();
    });

    it('redirects when no sessions are generated', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([]));

        $this->post(route('sessions.reconstruct'), ['mode' => SessionReconstructMode::Gaps->value])
            ->assertRedirect();
    });

    it('redirects with multiple sessions generated', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([Session::factory()->make(), Session::factory()->make()]));

        $this->post(route('sessions.reconstruct'), ['mode' => SessionReconstructMode::Gaps->value])
            ->assertRedirect();
    });
});

describe('reconstructFrom controller', function () {
    it('delegates to ReconstructSessionsFromDate and redirects', function () {
        ReconstructSessionsFromDate::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([Session::factory()->make()]));

        $this->post(route('sessions.reconstruct-from'), [
            'from_date' => now()->subDays(7)->toDateString(),
            'mode' => SessionReconstructMode::Gaps->value,
        ])->assertRedirect();
    });

    it('redirects when multiple sessions are generated', function () {
        ReconstructSessionsFromDate::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([Session::factory()->make(), Session::factory()->make()]));

        $this->post(route('sessions.reconstruct-from'), [
            'from_date' => now()->subDays(7)->toDateString(),
            'mode' => SessionReconstructMode::Gaps->value,
        ])->assertRedirect();
    });

    it('redirects when no sessions are generated', function () {
        ReconstructSessionsFromDate::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect([]));

        $this->post(route('sessions.reconstruct-from'), [
            'from_date' => now()->subDays(3)->toDateString(),
            'mode' => SessionReconstructMode::Gaps->value,
        ])->assertRedirect();
    });

    it('rejects a future from_date', function () {
        $this->post(route('sessions.reconstruct-from'), [
            'from_date' => now()->addDay()->toDateString(),
            'mode' => SessionReconstructMode::Gaps->value,
        ])->assertSessionHasErrors('from_date');
    });

    it('requires from_date', function () {
        $this->post(route('sessions.reconstruct-from'), [
            'mode' => SessionReconstructMode::Gaps->value,
        ])->assertSessionHasErrors('from_date');
    });
});
