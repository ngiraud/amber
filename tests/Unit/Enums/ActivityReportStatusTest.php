<?php

declare(strict_types=1);

use App\Enums\ActivityReportStatus;

pest()->group('activity-report');

describe('ActivityReportStatus', function () {
    it('has the correct integer values in workflow order', function () {
        expect(ActivityReportStatus::Draft->value)->toBeLessThan(ActivityReportStatus::Generating->value)
            ->and(ActivityReportStatus::Generating->value)->toBeLessThan(ActivityReportStatus::Failed->value)
            ->and(ActivityReportStatus::Failed->value)->toBeLessThan(ActivityReportStatus::Finalized->value)
            ->and(ActivityReportStatus::Finalized->value)->toBeLessThan(ActivityReportStatus::Sent->value);
    });

    it('returns a label for each case', function () {
        foreach (ActivityReportStatus::cases() as $status) {
            expect($status->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('serializes to value and label via toArray', function () {
        $array = ActivityReportStatus::Finalized->toArray();

        expect($array)->toHaveKeys(['value', 'label'])
            ->and($array['value'])->toBe(ActivityReportStatus::Finalized->value)
            ->and($array['label'])->toBeString();
    });

    it('exposes all cases via options()', function () {
        $options = ActivityReportStatus::options();

        expect($options)->toHaveCount(count(ActivityReportStatus::cases()));
        $options->each(fn ($option) => expect($option)->toHaveKeys(['value', 'label']));
    });
});
