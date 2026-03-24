<?php

declare(strict_types=1);

use App\Actions\Activity\ScanActivitySources;
use App\Actions\Session\ReconstructSessionsFromDate;
use App\Data\ScanActivityResult;
use App\Events\ActivityBackfillCompleted;
use App\Models\Session;
use App\Settings\ActivitySettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('commands', 'activity');

beforeEach(function () {
    Event::fake();

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->andReturn(new ScanActivityResult(collect(), collect()));

    ReconstructSessionsFromDate::fake()
        ->shouldReceive('handle')
        ->andReturn(collect());
});

it('uses default interval when last_scan_completed_at is null', function () {
    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = null;
    $settings->save();

    $expectedSince = CarbonImmutable::now()->subMinutes(3);

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->once()
        ->with(
            Mockery::on(fn ($arg) => $arg->diffInSeconds($expectedSince) < 5),
            Mockery::any(),
            Mockery::any(),
        )
        ->andReturn(new ScanActivityResult(collect(), collect()));

    $this->artisan('activity:scan');
});

it('uses default interval when gap does not exceed the scan window', function () {
    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = CarbonImmutable::now()->subMinutes(2);
    $settings->save();

    $expectedSince = CarbonImmutable::now()->subMinutes(3);

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->once()
        ->with(
            Mockery::on(fn ($arg) => $arg->diffInSeconds($expectedSince) < 5),
            Mockery::any(),
            Mockery::any(),
        )
        ->andReturn(new ScanActivityResult(collect(), collect()));

    $this->artisan('activity:scan');
});

it('uses last_scan_completed_at as since when gap exceeds the scan window', function () {
    $lastScan = CarbonImmutable::now()->subHours(3);

    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = $lastScan;
    $settings->save();

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->once()
        ->with(
            Mockery::on(fn ($arg) => $arg->diffInSeconds($lastScan) < 5),
            Mockery::any(),
            Mockery::any(),
        )
        ->andReturn(new ScanActivityResult(collect(), collect()));

    $this->artisan('activity:scan');
});

it('updates last_scan_completed_at after every scan', function () {
    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = null;
    $settings->save();

    $before = now();

    $this->artisan('activity:scan');

    $settings->refresh();

    expect($settings->last_scan_completed_at)->not->toBeNull()
        ->and($settings->last_scan_completed_at->diffInSeconds($before))->toBeLessThan(5);
});

it('dispatches ActivityBackfillCompleted when backfill finds events', function () {
    $lastScan = CarbonImmutable::now()->subHours(3);

    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = $lastScan;
    $settings->save();

    $sessions = Session::factory()->count(3)->create();

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->andReturn(new ScanActivityResult(collect(range(1, 7)), collect()));

    ReconstructSessionsFromDate::fake()
        ->shouldReceive('handle')
        ->once()
        ->andReturn($sessions);

    $this->artisan('activity:scan');

    Event::assertDispatched(ActivityBackfillCompleted::class, function ($event) {
        return $event->eventsCount === 7 && $event->sessionsCount === 3 && str_contains($event->period, 'hour');
    });
});

it('does not dispatch ActivityBackfillCompleted when backfill finds no events', function () {
    $lastScan = CarbonImmutable::now()->subHours(3);

    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = $lastScan;
    $settings->save();

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->andReturn(new ScanActivityResult(collect(), collect()));

    $this->artisan('activity:scan');

    Event::assertNotDispatched(ActivityBackfillCompleted::class);
});

it('does not dispatch ActivityBackfillCompleted for a normal scan', function () {
    $settings = app(ActivitySettings::class);
    $settings->last_scan_completed_at = CarbonImmutable::now()->subMinutes(2);
    $settings->save();

    ScanActivitySources::fake()
        ->shouldReceive('handle')
        ->andReturn(new ScanActivityResult(collect(range(1, 5)), collect()));

    $this->artisan('activity:scan');

    Event::assertNotDispatched(ActivityBackfillCompleted::class);
});
