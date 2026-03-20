<?php

declare(strict_types=1);

use Native\Desktop\Facades\AutoUpdater;

pest()->group('settings', 'updates');

describe('update controller', function () {
    describe('when updater is enabled', function () {
        beforeEach(fn () => config(['nativephp.updater.enabled' => true]));

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
    });

    describe('when updater is disabled', function () {
        beforeEach(fn () => config(['nativephp.updater.enabled' => false]));

        it('check is blocked', function () {
            AutoUpdater::shouldReceive('checkForUpdates')->never();

            $this->post(route('settings.updates.check'))
                ->assertForbidden();
        });

        it('install is blocked', function () {
            AutoUpdater::shouldReceive('quitAndInstall')->never();

            $this->post(route('settings.updates.install'))
                ->assertForbidden();
        });
    });
})->group('controllers');

describe('updates:check command', function () {
    it('calls AutoUpdater::checkForUpdates when updater is enabled', function () {
        config(['nativephp.updater.enabled' => true]);
        AutoUpdater::shouldReceive('checkForUpdates')->once();

        $this->artisan('updates:check')->assertExitCode(0);
    });

    it('does nothing when updater is disabled', function () {
        config(['nativephp.updater.enabled' => false]);
        AutoUpdater::shouldReceive('checkForUpdates')->never();

        $this->artisan('updates:check')->assertExitCode(0);
    });
})->group('commands');
