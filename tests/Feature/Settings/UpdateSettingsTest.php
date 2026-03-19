<?php

declare(strict_types=1);

use Native\Desktop\Facades\AutoUpdater;

pest()->group('settings', 'updates');

describe('update controller', function () {
    it('check calls AutoUpdater::checkForUpdates and redirects back', function () {
        AutoUpdater::shouldReceive('checkForUpdates')->once();

        $this->post(route('settings.updates.check'))
            ->assertRedirect();
    });

    it('install calls AutoUpdater::quitAndInstall and redirects back', function () {
        AutoUpdater::shouldReceive('quitAndInstall')->once();

        $this->post(route('settings.updates.install'))
            ->assertRedirect();
    });
})->group('controllers');

describe('updates:check command', function () {
    it('calls AutoUpdater::checkForUpdates', function () {
        AutoUpdater::shouldReceive('checkForUpdates')->once();

        $this->artisan('updates:check')->assertExitCode(0);
    });
})->group('commands');
