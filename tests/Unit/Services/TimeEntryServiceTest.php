<?php

declare(strict_types=1);

use App\Enums\RoundingStrategy;
use App\Services\TimeEntryService;

pest()->group('services');

describe('TimeEntryService', function () {
    beforeEach(function () {
        $this->service = new TimeEntryService;
    });

    it('returns 0 for zero minutes', function () {
        expect($this->service->roundMinutesAccordingStrategy(0, RoundingStrategy::Quarter))->toBe(0);
    });

    it('returns 0 for negative minutes', function () {
        expect($this->service->roundMinutesAccordingStrategy(-10, RoundingStrategy::Quarter))->toBe(0);
    });

    it('rounds up to nearest quarter hour', function () {
        expect($this->service->roundMinutesAccordingStrategy(1, RoundingStrategy::Quarter))->toBe(15)
            ->and($this->service->roundMinutesAccordingStrategy(14, RoundingStrategy::Quarter))->toBe(15)
            ->and($this->service->roundMinutesAccordingStrategy(15, RoundingStrategy::Quarter))->toBe(15)
            ->and($this->service->roundMinutesAccordingStrategy(16, RoundingStrategy::Quarter))->toBe(30)
            ->and($this->service->roundMinutesAccordingStrategy(60, RoundingStrategy::Quarter))->toBe(60);
    });

    it('rounds up to nearest half hour', function () {
        expect($this->service->roundMinutesAccordingStrategy(1, RoundingStrategy::HalfHour))->toBe(30)
            ->and($this->service->roundMinutesAccordingStrategy(30, RoundingStrategy::HalfHour))->toBe(30)
            ->and($this->service->roundMinutesAccordingStrategy(31, RoundingStrategy::HalfHour))->toBe(60);
    });

    it('rounds up to nearest hour', function () {
        expect($this->service->roundMinutesAccordingStrategy(1, RoundingStrategy::Hour))->toBe(60)
            ->and($this->service->roundMinutesAccordingStrategy(60, RoundingStrategy::Hour))->toBe(60)
            ->and($this->service->roundMinutesAccordingStrategy(61, RoundingStrategy::Hour))->toBe(120);
    });
});
