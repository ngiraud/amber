<?php

declare(strict_types=1);

use App\Enums\RoundingStrategy;
use App\Services\TimeEntryService;

pest()->group('services', 'time-entry');

it('rounds up to nearest quarter hour', function () {
    $action = app(TimeEntryService::class);

    expect($action->roundMinutesAccordingStrategy(1, RoundingStrategy::Quarter))->toBe(15)
        ->and($action->roundMinutesAccordingStrategy(14, RoundingStrategy::Quarter))->toBe(15)
        ->and($action->roundMinutesAccordingStrategy(15, RoundingStrategy::Quarter))->toBe(15)
        ->and($action->roundMinutesAccordingStrategy(16, RoundingStrategy::Quarter))->toBe(30)
        ->and($action->roundMinutesAccordingStrategy(30, RoundingStrategy::Quarter))->toBe(30)
        ->and($action->roundMinutesAccordingStrategy(31, RoundingStrategy::Quarter))->toBe(45)
        ->and($action->roundMinutesAccordingStrategy(60, RoundingStrategy::Quarter))->toBe(60);
});

it('rounds up to nearest half hour', function () {
    $action = app(TimeEntryService::class);

    expect($action->roundMinutesAccordingStrategy(1, RoundingStrategy::HalfHour))->toBe(30)
        ->and($action->roundMinutesAccordingStrategy(29, RoundingStrategy::HalfHour))->toBe(30)
        ->and($action->roundMinutesAccordingStrategy(30, RoundingStrategy::HalfHour))->toBe(30)
        ->and($action->roundMinutesAccordingStrategy(31, RoundingStrategy::HalfHour))->toBe(60)
        ->and($action->roundMinutesAccordingStrategy(60, RoundingStrategy::HalfHour))->toBe(60);
});

it('rounds up to nearest hour', function () {
    $action = app(TimeEntryService::class);

    expect($action->roundMinutesAccordingStrategy(1, RoundingStrategy::Hour))->toBe(60)
        ->and($action->roundMinutesAccordingStrategy(59, RoundingStrategy::Hour))->toBe(60)
        ->and($action->roundMinutesAccordingStrategy(60, RoundingStrategy::Hour))->toBe(60)
        ->and($action->roundMinutesAccordingStrategy(61, RoundingStrategy::Hour))->toBe(120);
});

it('returns 0 for zero or negative input', function () {
    $action = app(TimeEntryService::class);

    expect($action->roundMinutesAccordingStrategy(0, RoundingStrategy::Quarter))->toBe(0)
        ->and($action->roundMinutesAccordingStrategy(-5, RoundingStrategy::Quarter))->toBe(0);
});
