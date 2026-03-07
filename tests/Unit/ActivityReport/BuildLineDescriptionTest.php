<?php

declare(strict_types=1);

use App\Actions\ActivityReport\BuildLineDescription;
use App\Data\DayContext;

pest()->group('activity-report', 'actions');

describe('BuildLineDescription action', function () {
    it('returns empty string when context has no labels or details', function () {
        $context = new DayContext(labels: [], details: [], filesChanged: 0);

        $result = BuildLineDescription::make()->handle($context);

        expect($result)->toBe('');
    });

    it('returns labels joined by comma', function () {
        $context = new DayContext(
            labels: ['feat: login', 'fix: token refresh'],
            details: [],
            filesChanged: 0,
        );

        $result = BuildLineDescription::make()->handle($context);

        expect($result)->toBe('feat: login, fix: token refresh');
    });

    it('returns details separated by pipe when only details', function () {
        $context = new DayContext(
            labels: [],
            details: ['Add login page', 'Fix token bug'],
            filesChanged: 0,
        );

        $result = BuildLineDescription::make()->handle($context);

        expect($result)->toBe('Add login page | Fix token bug');
    });

    it('combines labels and details with pipe separator', function () {
        $context = new DayContext(
            labels: ['feat: auth'],
            details: ['Implement JWT tokens'],
            filesChanged: 5,
        );

        $result = BuildLineDescription::make()->handle($context);

        expect($result)->toBe('feat: auth | Implement JWT tokens');
    });

    it('deduplicates labels', function () {
        $context = new DayContext(
            labels: ['main', 'main', 'feat/login'],
            details: [],
            filesChanged: 0,
        );

        $result = BuildLineDescription::make()->handle($context);

        expect($result)->toBe('main, feat/login');
    });
});
