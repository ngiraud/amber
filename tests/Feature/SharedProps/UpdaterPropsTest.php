<?php

declare(strict_types=1);

pest()->group('settings', 'updates');

test('updaterEnabled is true when updater is enabled', function () {
    config(['nativephp.updater.enabled' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('updaterEnabled', true));
});

test('updaterEnabled is false when updater is disabled', function () {
    config(['nativephp.updater.enabled' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('updaterEnabled', false));
});
