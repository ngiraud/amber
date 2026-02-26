<?php

declare(strict_types=1);

use App\Actions\TimeEntry\RoundMinutes;
use App\Enums\RoundingStrategy;

pest()->group('actions', 'time-entry');

describe('RoundMinutes action', function () {
    it('rounds up to nearest quarter hour', function () {
        $action = RoundMinutes::make();

        expect($action->handle(1, RoundingStrategy::Quarter))->toBe(15)
            ->and($action->handle(14, RoundingStrategy::Quarter))->toBe(15)
            ->and($action->handle(15, RoundingStrategy::Quarter))->toBe(15)
            ->and($action->handle(16, RoundingStrategy::Quarter))->toBe(30)
            ->and($action->handle(30, RoundingStrategy::Quarter))->toBe(30)
            ->and($action->handle(31, RoundingStrategy::Quarter))->toBe(45)
            ->and($action->handle(60, RoundingStrategy::Quarter))->toBe(60);
    });

    it('rounds up to nearest half hour', function () {
        $action = RoundMinutes::make();

        expect($action->handle(1, RoundingStrategy::HalfHour))->toBe(30)
            ->and($action->handle(29, RoundingStrategy::HalfHour))->toBe(30)
            ->and($action->handle(30, RoundingStrategy::HalfHour))->toBe(30)
            ->and($action->handle(31, RoundingStrategy::HalfHour))->toBe(60)
            ->and($action->handle(60, RoundingStrategy::HalfHour))->toBe(60);
    });

    it('rounds up to nearest hour', function () {
        $action = RoundMinutes::make();

        expect($action->handle(1, RoundingStrategy::Hour))->toBe(60)
            ->and($action->handle(59, RoundingStrategy::Hour))->toBe(60)
            ->and($action->handle(60, RoundingStrategy::Hour))->toBe(60)
            ->and($action->handle(61, RoundingStrategy::Hour))->toBe(120);
    });

    it('returns 0 for zero or negative input', function () {
        $action = RoundMinutes::make();

        expect($action->handle(0, RoundingStrategy::Quarter))->toBe(0)
            ->and($action->handle(-5, RoundingStrategy::Quarter))->toBe(0);
    });
});
