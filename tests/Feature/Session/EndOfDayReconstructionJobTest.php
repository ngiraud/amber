<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructDailySessions;
use App\Jobs\EndOfDayReconstructionJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('session');

describe('EndOfDayReconstructionJob', function () {
    beforeEach(fn () => Event::fake());

    it('calls ReconstructDaySessions for yesterday by default', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $date) => $date->isYesterday()));

        dispatch_sync(new EndOfDayReconstructionJob);
    });

    it('calls ReconstructDaySessions for a specific date when provided', function () {
        $date = '2026-02-20';

        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $d) => $d->toDateString() === $date));

        dispatch_sync(new EndOfDayReconstructionJob($date));
    });
});
