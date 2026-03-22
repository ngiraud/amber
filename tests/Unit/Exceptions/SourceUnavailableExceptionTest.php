<?php

declare(strict_types=1);

use App\Enums\ActivityEventSourceType;
use App\Exceptions\SourceUnavailableException;

pest()->group('exceptions');

describe('SourceUnavailableException', function () {
    it('builds a message with the source label and install command', function () {
        $exception = new SourceUnavailableException(ActivityEventSourceType::Git);

        expect($exception->getMessage())
            ->toContain('Git')
            ->toContain('Install with:');
    });

    it('includes the source type on the exception', function () {
        $exception = new SourceUnavailableException(ActivityEventSourceType::GitHub);

        expect($exception->source)->toBe(ActivityEventSourceType::GitHub);
    });

});
