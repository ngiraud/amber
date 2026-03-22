<?php

declare(strict_types=1);

use App\Actions\Session\StopSession;
use Mockery\MockInterface;

pest()->group('support');

describe('Fakeable', function () {
    it('isFake returns false when not faked', function () {
        StopSession::clearResolvedInstance();

        expect(StopSession::isFake())->toBeFalse();
    });

    it('isFake returns true after fake is applied', function () {
        StopSession::fake();

        expect(StopSession::isFake())->toBeTrue();
    });

    it('clearResolvedInstance removes the fake from the container', function () {
        StopSession::fake();
        expect(StopSession::isFake())->toBeTrue();

        StopSession::clearResolvedInstance();

        expect(StopSession::isFake())->toBeFalse();
    });

    it('swap replaces the container binding and returns the instance', function () {
        $mock = Mockery::mock(StopSession::class);

        $returned = StopSession::swap($mock);

        expect($returned)->toBe($mock)
            ->and(app(StopSession::class))->toBe($mock);
    });

    it('fakePartial returns a partial mock', function () {
        $mock = StopSession::fakePartial();

        expect($mock)->toBeInstanceOf(MockInterface::class)
            ->and(StopSession::isFake())->toBeTrue();
    });
});
