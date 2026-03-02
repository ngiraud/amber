<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructDailySessions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('commands', 'session');

describe('sessions:reconstruct command', function () {
    beforeEach(fn () => Event::fake());

    it('reconstructs sessions for today by default', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $date) => $date->isToday()))
            ->andReturn(collect());

        $this->artisan('sessions:reconstruct')->assertSuccessful();
    });

    it('reconstructs sessions for a specific date when provided', function () {
        $date = '2026-02-20';

        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $d) => $d->toDateString() === $date))
            ->andReturn(collect());

        $this->artisan('sessions:reconstruct', ['--date' => $date])->assertSuccessful();
    });
});
