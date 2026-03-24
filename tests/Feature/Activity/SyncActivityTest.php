<?php

declare(strict_types=1);

use App\Actions\Activity\ScanActivitySources;
use App\Actions\Session\ReconstructSessionsFromDate;
use App\Data\ScanActivityResult;
use App\Enums\ActivityEventSourceType;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('controllers', 'activity');

describe('SyncActivityController', function () {
    beforeEach(fn () => Event::fake());

    it('delegates to ScanActivitySources and returns event count', function () {
        $since = CarbonImmutable::now()->subHour();
        $until = CarbonImmutable::now();

        ScanActivitySources::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->toIso8601String() === $since->toIso8601String()),
                Mockery::on(fn ($arg) => $arg->toIso8601String() === $until->toIso8601String()),
                Mockery::on(fn ($arg) => $arg->first() === ActivityEventSourceType::Git),
            )
            ->andReturn(new ScanActivityResult(collect(range(1, 3)), collect()));

        $response = $this->postJson(route('activity.sync'), [
            'since' => $since->toIso8601String(),
            'until' => $until->toIso8601String(),
            'source_type' => ActivityEventSourceType::Git->value,
        ]);

        $response->assertOk()->assertJson(['count' => 3, 'source_errors' => []]);
    });

    it('returns 422 when source_type is missing', function () {
        $this->postJson(route('activity.sync'), [
            'since' => CarbonImmutable::now()->subHour()->toIso8601String(),
            'until' => CarbonImmutable::now()->toIso8601String(),
        ])->assertUnprocessable();
    });

    it('returns 422 when until is before since', function () {
        $now = CarbonImmutable::now();

        $this->postJson(route('activity.sync'), [
            'since' => $now->toIso8601String(),
            'until' => $now->subHour()->toIso8601String(),
            'source_type' => ActivityEventSourceType::Git->value,
        ])->assertUnprocessable();
    });
});

describe('ReconstructActivityController', function () {
    it('reconstructs sessions from a given date and returns count', function () {
        $sessions = Session::factory()->count(4)->create();
        $since = CarbonImmutable::now()->subDays(2)->toDateString();

        ReconstructSessionsFromDate::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn($sessions);

        $response = $this->postJson(route('activity.reconstruct'), ['since' => $since]);

        $response->assertOk()->assertJson(['sessions_count' => 4]);
    });

    it('returns 422 when since is missing', function () {
        $this->postJson(route('activity.reconstruct'), [])->assertUnprocessable();
    });
});
