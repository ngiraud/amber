<?php

declare(strict_types=1);

use App\Actions\Settings\UpdateActivitySettings;
use App\Settings\ActivitySettings;

pest()->group('settings', 'activity');

describe('activity settings', function () {
    it('renders the activity tab with required props', function () {
        $this->get(route('settings.activity'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Activity')
                ->has('activitySettings')
            );
    });

    it('delegates PUT to UpdateActivitySettings and redirects back', function () {
        UpdateActivitySettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['idle_timeout_minutes'] === 45));

        $this->put(route('settings.activity.update'), [
            'idle_timeout_minutes' => 45,
            'scan_interval_minutes' => 5,
            'block_end_padding_minutes' => 0,
            'manual_session_reminder_minutes' => 0,
        ])->assertRedirectBack();
    });

    it('validates idle_timeout_minutes is within bounds', function () {
        $this->put(route('settings.activity.update'), ['idle_timeout_minutes' => 0])
            ->assertInvalid(['idle_timeout_minutes']);

        $this->put(route('settings.activity.update'), ['idle_timeout_minutes' => 200])
            ->assertInvalid(['idle_timeout_minutes']);
    });

    it('validates scan_interval_minutes is within bounds', function () {
        $this->put(route('settings.activity.update'), ['scan_interval_minutes' => 0])
            ->assertInvalid(['scan_interval_minutes']);

        $this->put(route('settings.activity.update'), ['scan_interval_minutes' => 60])
            ->assertInvalid(['scan_interval_minutes']);
    });
})->group('controllers');

describe('UpdateActivitySettings action', function () {
    it('persists timing settings', function () {
        UpdateActivitySettings::make()->handle([
            'idle_timeout_minutes' => 45,
            'scan_interval_minutes' => 5,
        ]);

        $settings = app(ActivitySettings::class);
        expect($settings->idle_timeout_minutes)->toBe(45)
            ->and($settings->scan_interval_minutes)->toBe(5);
    });
})->group('actions');
