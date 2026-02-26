<?php

declare(strict_types=1);

use App\Actions\TimeEntry\ReconstructDayEntries;
use App\Jobs\EndOfDayReconstructionJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('time-entry');

describe('EndOfDayReconstructionJob', function () {
    beforeEach(fn () => Event::fake());

    it('calls ReconstructDayEntries for yesterday by default', function () {
        ReconstructDayEntries::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $date) => $date->isYesterday()));

        dispatch_sync(new EndOfDayReconstructionJob);
    });

    it('calls ReconstructDayEntries for a specific date when provided', function () {
        $date = '2026-02-20';

        ReconstructDayEntries::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn (CarbonImmutable $d) => $d->toDateString() === $date));

        dispatch_sync(new EndOfDayReconstructionJob($date));
    });
});
