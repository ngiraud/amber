<?php

declare(strict_types=1);

use App\Enums\RoundingStrategy;

pest()->group('enums');

it('rounds up to nearest quarter hour', function () {
    $strategy = RoundingStrategy::Quarter;

    expect($strategy->round(1))->toBe(15)
        ->and($strategy->round(14))->toBe(15)
        ->and($strategy->round(15))->toBe(15)
        ->and($strategy->round(16))->toBe(30)
        ->and($strategy->round(30))->toBe(30)
        ->and($strategy->round(31))->toBe(45)
        ->and($strategy->round(60))->toBe(60);
});

it('rounds up to nearest half hour', function () {
    $strategy = RoundingStrategy::HalfHour;

    expect($strategy->round(1))->toBe(30)
        ->and($strategy->round(29))->toBe(30)
        ->and($strategy->round(30))->toBe(30)
        ->and($strategy->round(31))->toBe(60)
        ->and($strategy->round(60))->toBe(60);
});

it('rounds up to nearest hour', function () {
    $strategy = RoundingStrategy::Hour;

    expect($strategy->round(1))->toBe(60)
        ->and($strategy->round(59))->toBe(60)
        ->and($strategy->round(60))->toBe(60)
        ->and($strategy->round(61))->toBe(120);
});

it('returns 0 for zero or negative input', function () {
    $strategy = RoundingStrategy::Quarter;

    expect($strategy->round(0))->toBe(0)
        ->and($strategy->round(-5))->toBe(0);
});
