<?php

declare(strict_types=1);

use App\Enums\ActivityReportStep;
use App\Events\ActivityReportProgress;
use Illuminate\Broadcasting\Channel;

pest()->group('activity-report');

describe('ActivityReportProgress', function () {
    it('broadcasts on the nativephp channel', function () {
        $event = new ActivityReportProgress('report-id', ActivityReportStep::Completed);

        $channels = $event->broadcastOn();

        expect($channels)->toHaveCount(1)
            ->and($channels[0])->toBeInstanceOf(Channel::class)
            ->and($channels[0]->name)->toBe('nativephp');
    });

    it('exposes constructor properties', function () {
        $event = new ActivityReportProgress('report-id', ActivityReportStep::Failed, 'Something went wrong');

        expect($event->reportId)->toBe('report-id')
            ->and($event->step)->toBe(ActivityReportStep::Failed)
            ->and($event->message)->toBe('Something went wrong');
    });

    it('has a null message by default', function () {
        $event = new ActivityReportProgress('report-id', ActivityReportStep::Completed);

        expect($event->message)->toBeNull();
    });
});
