<?php

declare(strict_types=1);

use App\Actions\Settings\UpdateActivitySettings;
use App\Actions\Settings\UpdateActivitySourceSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Services\FileWatcherService;
use App\Settings\ActivitySettings;
use App\Settings\ActivitySourceSettings;
use App\Settings\GeneralSettings;

pest()->group('settings');

// ── General ────────────────────────────────────────────────────────────────

describe('general settings', function () {
    it('renders the general tab with required props', function () {
        $this->get(route('settings.general'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Edit')
                ->where('tab', 'general')
                ->has('generalSettings')
                ->has('timezones')
                ->has('locales')
            );
    });

    it('delegates PUT to UpdateGeneralSettings and redirects', function () {
        UpdateGeneralSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['company_name'] === 'Acme Corp'));

        $this->put(route('settings.general.update'), ['company_name' => 'Acme Corp'])
            ->assertRedirectToRoute('settings.general');
    });

    it('validates default_rounding_strategy is a valid enum value', function () {
        $this->put(route('settings.general.update'), ['default_rounding_strategy' => 999])
            ->assertInvalid(['default_rounding_strategy']);
    });

    it('validates timezone is a valid timezone identifier', function () {
        $this->put(route('settings.general.update'), ['timezone' => 'Invalid/Zone'])
            ->assertInvalid(['timezone']);
    });

    it('accepts a valid timezone', function () {
        UpdateGeneralSettings::fake()->shouldReceive('handle')->once();

        $this->put(route('settings.general.update'), ['timezone' => 'Europe/Paris'])
            ->assertRedirectToRoute('settings.general');
    });

    it('validates locale must be in allowed list', function () {
        $this->put(route('settings.general.update'), ['locale' => 'de'])
            ->assertInvalid(['locale']);
    });
})->group('controllers');

describe('UpdateGeneralSettings action', function () {
    it('persists general settings', function () {
        UpdateGeneralSettings::make()->handle([
            'company_name' => 'Acme Corp',
            'default_daily_reference_hours' => 7,
        ]);

        $settings = app(GeneralSettings::class);
        expect($settings->company_name)->toBe('Acme Corp')
            ->and($settings->default_daily_reference_hours)->toBe(7);
    });
})->group('actions');

// ── Activity ───────────────────────────────────────────────────────────────

describe('activity settings', function () {
    it('renders the activity tab with required props', function () {
        $this->get(route('settings.activity'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Edit')
                ->where('tab', 'activity')
                ->has('activitySettings')
            );
    });

    it('delegates PUT to UpdateActivitySettings and redirects', function () {
        UpdateActivitySettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['idle_timeout_minutes'] === 45));

        $this->put(route('settings.activity.update'), ['idle_timeout_minutes' => 45])
            ->assertRedirectToRoute('settings.activity');
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

// ── Sources ────────────────────────────────────────────────────────────────

describe('source settings', function () {
    it('renders the sources tab with required props', function () {
        $this->get(route('settings.sources'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Edit')
                ->where('tab', 'sources')
                ->has('activitySourceSettings')
            );
    });

    it('delegates PUT to UpdateActivitySourceSettings and redirects', function () {
        UpdateActivitySourceSettings::fake()
            ->shouldReceive('handle')
            ->once();

        $this->put(route('settings.sources.update'), ['git' => ['enabled' => false, 'author_emails' => []]])
            ->assertRedirectToRoute('settings.sources');
    });

    it('validates git author emails are valid email addresses', function () {
        $this->put(route('settings.sources.update'), [
            'git' => ['enabled' => true, 'author_emails' => ['not-an-email']],
        ])->assertInvalid(['git.author_emails.0']);
    });

    it('validates source enabled fields are boolean', function () {
        $this->put(route('settings.sources.update'), [
            'git' => ['enabled' => 'not-a-bool'],
        ])->assertInvalid(['git.enabled']);
    });
})->group('controllers');

describe('UpdateActivitySourceSettings action', function () {
    it('persists source settings as DTOs', function () {
        UpdateActivitySourceSettings::make()->handle([
            'git' => ['enabled' => false, 'author_emails' => ['dev@example.com']],
            'github' => ['enabled' => true, 'username' => 'johndoe'],
        ]);

        $settings = app(ActivitySourceSettings::class);
        expect($settings->git->enabled)->toBeFalse()
            ->and($settings->git->author_emails)->toBe(['dev@example.com'])
            ->and($settings->github->username)->toBe('johndoe');
    });

    it('stops the file watcher when fswatch is disabled', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->fswatch = new FswatchSourceConfig(true, 3, [], []);
        $sourceSettings->save();

        $watcher = Mockery::mock(FileWatcherService::class);
        $watcher->shouldReceive('stop')->once();
        app()->instance(FileWatcherService::class, $watcher);

        UpdateActivitySourceSettings::make()->handle([
            'fswatch' => ['enabled' => false, 'debounce_seconds' => 3, 'excluded_patterns' => [], 'allowed_extensions' => []],
        ]);
    });

    it('starts the file watcher when fswatch is enabled', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->fswatch = new FswatchSourceConfig(false, 3, [], []);
        $sourceSettings->save();

        $watcher = Mockery::mock(FileWatcherService::class);
        $watcher->shouldReceive('start')->once();
        app()->instance(FileWatcherService::class, $watcher);

        UpdateActivitySourceSettings::make()->handle([
            'fswatch' => ['enabled' => true, 'debounce_seconds' => 3, 'excluded_patterns' => [], 'allowed_extensions' => []],
        ]);
    });

    it('restarts the file watcher when debounce changes while enabled', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->fswatch = new FswatchSourceConfig(true, 3, [], []);
        $sourceSettings->save();

        $watcher = Mockery::mock(FileWatcherService::class);
        $watcher->shouldReceive('restart')->once();
        app()->instance(FileWatcherService::class, $watcher);

        UpdateActivitySourceSettings::make()->handle([
            'fswatch' => ['enabled' => true, 'debounce_seconds' => 5, 'excluded_patterns' => [], 'allowed_extensions' => []],
        ]);
    });

    it('does not touch the file watcher when unrelated settings change', function () {
        $watcher = Mockery::mock(FileWatcherService::class);
        $watcher->shouldNotReceive('stop');
        $watcher->shouldNotReceive('start');
        $watcher->shouldNotReceive('restart');
        app()->instance(FileWatcherService::class, $watcher);

        UpdateActivitySourceSettings::make()->handle([
            'git' => ['enabled' => true, 'author_emails' => []],
        ]);
    });
})->group('actions');
