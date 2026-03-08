<?php

declare(strict_types=1);

use App\Events\SessionStarted;
use App\Events\SessionStopped;
use App\Listeners\UpdateDockBadgeOnSessionChange;
use App\Models\Session;
use Illuminate\Support\Facades\Http;

pest()->group('session', 'listeners');

describe('UpdateDockBadgeOnSessionChange listener', function () {
    it('sets badge count to 1 when a session starts', function () {
        Http::fake(['*/app/badge-count' => Http::response([])]);

        $session = Session::factory()->make();
        $listener = app(UpdateDockBadgeOnSessionChange::class);
        $listener->handle(new SessionStarted($session));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'app/badge-count')
            && $request->data()['count'] === 1
        );
    });

    it('sets badge count to 0 when a session stops', function () {
        Http::fake(['*/app/badge-count' => Http::response([])]);

        $session = Session::factory()->make();
        $listener = app(UpdateDockBadgeOnSessionChange::class);
        $listener->handle(new SessionStopped($session));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'app/badge-count')
            && $request->data()['count'] === 0
        );
    });
})->group('listeners');
