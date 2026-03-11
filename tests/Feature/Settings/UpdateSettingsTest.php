<?php

declare(strict_types=1);

use App\Actions\Settings\TestActivitySourceConnection;
use App\Actions\Settings\TestAiConnection;
use App\Actions\Settings\UpdateActivitySettings;
use App\Actions\Settings\UpdateActivitySourceSettings;
use App\Actions\Settings\UpdateAiSettings;
use App\Actions\Settings\UpdateGeneralSettings;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Enums\AiProvider;
use App\Services\ActivitySources\ClaudeCodeActivitySource;
use App\Services\ActivitySources\GitActivitySource;
use App\Services\ActivitySources\GitHubActivitySource;
use App\Services\FileWatcherService;
use App\Settings\ActivitySettings;
use App\Settings\ActivitySourceSettings;
use App\Settings\AiSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

pest()->group('settings');

// ── General ────────────────────────────────────────────────────────────────

describe('general settings', function () {
    it('renders the general tab with required props', function () {
        $this->get(route('settings.general'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/General')
                ->has('generalSettings')
                ->has('timezones')
                //                ->has('locales')
            );
    });

    it('delegates PUT to UpdateGeneralSettings and redirects', function () {
        UpdateGeneralSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['company_name'] === 'Acme Corp'));

        $this->put(route('settings.general.update'), [
            'company_name' => 'Acme Corp',
            'default_rounding_strategy' => 15,
            'timezone' => 'Europe/Paris',
            //            'locale' => 'fr',
            'theme' => 'system',
            'open_at_login' => false,
        ])->assertRedirectToRoute('settings.general');
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

        $this->put(route('settings.general.update'), [
            'default_rounding_strategy' => 15,
            'timezone' => 'Europe/Paris',
            //            'locale' => 'fr',
            'theme' => 'system',
            'open_at_login' => false,
        ])->assertRedirectToRoute('settings.general');
    });

    //    it('validates locale must be in allowed list', function () {
    //        $this->put(route('settings.general.update'), ['locale' => 'de'])
    //            ->assertInvalid(['locale']);
    //    });
})->group('controllers');

describe('UpdateGeneralSettings action', function () {
    beforeEach(function () {
        Http::fake([
            '*/system/theme' => Http::response(['result' => 'system']),
            '*/app/open-at-login' => Http::response([]),
        ]);
    });

    it('persists general settings', function () {
        UpdateGeneralSettings::make()->handle([
            'company_name' => 'Acme Corp',
            'default_daily_reference_hours' => 7,
        ]);

        $settings = app(GeneralSettings::class);
        expect($settings->company_name)->toBe('Acme Corp')
            ->and($settings->default_daily_reference_hours)->toBe(7);
    });

    it('persists open_at_login and applies it', function () {
        UpdateGeneralSettings::make()->handle([
            'open_at_login' => true,
        ]);

        expect(app(GeneralSettings::class)->open_at_login)->toBeTrue();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'app/open-at-login')
            && $request->data()['open'] === true
        );
    });
})->group('actions');

// ── Activity ───────────────────────────────────────────────────────────────

describe('activity settings', function () {
    it('renders the activity tab with required props', function () {
        $this->get(route('settings.activity'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Activity')
                ->has('activitySettings')
            );
    });

    it('delegates PUT to UpdateActivitySettings and redirects', function () {
        UpdateActivitySettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['idle_timeout_minutes'] === 45));

        $this->put(route('settings.activity.update'), [
            'idle_timeout_minutes' => 45,
            'scan_interval_minutes' => 5,
            'block_end_padding_minutes' => 0,
            'manual_session_reminder_minutes' => 0,
        ])->assertRedirectToRoute('settings.activity');
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
                ->component('settings/Sources')
                ->has('sources')
                ->has('sources.0.value')
                ->has('sources.0.fields')
                ->has('sources.0.config')
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

describe('test source connection', function () {
    it('delegates POST to TestActivitySourceConnection and returns JSON', function () {
        TestActivitySourceConnection::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(ActivityEventSourceType::Git)
            ->andReturn(true);

        $this->postJson(route('settings.sources.test', ['source' => 'git']))
            ->assertSuccessful()
            ->assertJson(['available' => true]);
    });

    it('returns available false when action returns false', function () {
        TestActivitySourceConnection::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(false);

        $this->postJson(route('settings.sources.test', ['source' => 'claude_code']))
            ->assertSuccessful()
            ->assertJson(['available' => false]);
    });
})->group('controllers');

describe('TestActivitySourceConnection action', function () {
    it('returns true when git is available', function () {
        $source = Mockery::mock(GitActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(true);
        app()->instance(GitActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::Git))->toBeTrue();
    });

    it('returns false when git is unavailable', function () {
        $source = Mockery::mock(GitActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(false);
        app()->instance(GitActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::Git))->toBeFalse();
    });

    it('returns true when gh is authenticated', function () {
        $source = Mockery::mock(GitHubActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(true);
        app()->instance(GitHubActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::GitHub))->toBeTrue();
    });

    it('returns false when gh is not authenticated', function () {
        $source = Mockery::mock(GitHubActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(false);
        app()->instance(GitHubActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::GitHub))->toBeFalse();
    });

    it('returns true when claude is available', function () {
        $source = Mockery::mock(ClaudeCodeActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(true);
        app()->instance(ClaudeCodeActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::ClaudeCode))->toBeTrue();
    });

    it('returns false when claude is unavailable', function () {
        $source = Mockery::mock(ClaudeCodeActivitySource::class);
        $source->shouldReceive('isAvailable')->once()->andReturn(false);
        app()->instance(ClaudeCodeActivitySource::class, $source);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::ClaudeCode))->toBeFalse();
    });

    it('returns true when fswatch is available', function () {
        Process::fake(['*fswatch*' => Process::result(exitCode: 0)]);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::Fswatch))->toBeTrue();
    });

    it('returns false when fswatch is unavailable', function () {
        Process::fake(['*fswatch*' => Process::result(exitCode: 1)]);

        expect(TestActivitySourceConnection::make()->handle(ActivityEventSourceType::Fswatch))->toBeFalse();
    });
})->group('actions');

describe('UpdateActivitySourceSettings action', function () {
    beforeEach(function () {
        TestActivitySourceConnection::fake()
            ->shouldReceive('handle')
            ->andReturn(true);
    });

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

        TestActivitySourceConnection::fake()->shouldReceive('handle')->andReturn(true);

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

    it('throws a validation error when enabling a source whose tool is not installed', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->git = new App\Data\ActivitySourceConfigs\GitSourceConfig(false, []);
        $sourceSettings->save();

        TestActivitySourceConnection::fake()
            ->shouldReceive('handle')
            ->with(ActivityEventSourceType::Git)
            ->once()
            ->andReturn(false);

        UpdateActivitySourceSettings::make()->handle([
            'git' => ['enabled' => true, 'author_emails' => []],
        ]);
    })->throws(Illuminate\Validation\ValidationException::class);

    it('does not save settings when availability check fails', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->git = new App\Data\ActivitySourceConfigs\GitSourceConfig(false, []);
        $sourceSettings->save();

        TestActivitySourceConnection::fake()->shouldReceive('handle')->andReturn(false);

        try {
            UpdateActivitySourceSettings::make()->handle([
                'git' => ['enabled' => true, 'author_emails' => ['new@example.com']],
            ]);
        } catch (Illuminate\Validation\ValidationException) {
            // expected
        }

        expect(app(ActivitySourceSettings::class)->git->enabled)->toBeFalse();
    });

    it('does not check availability when a source stays enabled', function () {
        // git defaults to enabled=true; passing enabled=true again should not trigger a check
        $settings = app(ActivitySourceSettings::class);
        $settings->setConfig(ActivityEventSourceType::Git, ['enabled' => true]);
        $settings->save();

        TestActivitySourceConnection::fake()->shouldNotReceive('handle');

        UpdateActivitySourceSettings::make()->handle([
            'git' => ['enabled' => true, 'author_emails' => []],
        ]);
    });

    it('does not check availability when a source is being disabled', function () {
        TestActivitySourceConnection::fake()->shouldNotReceive('handle');

        UpdateActivitySourceSettings::make()->handle([
            'git' => ['enabled' => false, 'author_emails' => []],
        ]);
    });

    it('returns a validation error keyed by source field when tool is unavailable', function () {
        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->github = new App\Data\ActivitySourceConfigs\GitHubSourceConfig(false, null);
        $sourceSettings->save();

        TestActivitySourceConnection::fake()->shouldReceive('handle')->andReturn(false);

        try {
            UpdateActivitySourceSettings::make()->handle([
                'github' => ['enabled' => true, 'username' => 'octocat'],
            ]);
            expect(true)->toBeFalse('expected ValidationException');
        } catch (Illuminate\Validation\ValidationException $e) {
            expect($e->errors())->toHaveKey('github.enabled');
        }
    });
})->group('actions');

// ── AI ─────────────────────────────────────────────────────────────────────

describe('ai settings', function () {
    it('renders the ai tab with required props', function () {
        $this->get(route('settings.ai'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Ai')
                ->has('aiSettings')
                ->has('providers')
            );
    });

    it('delegates PUT to UpdateAiSettings and redirects', function () {
        UpdateAiSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['enabled'] === true && $data['provider'] === 'anthropic'));

        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'anthropic',
            'api_key' => 'sk-test',
            'summary_language' => 'fr',
        ])->assertRedirectToRoute('settings.ai');
    });

    it('validates provider is a valid AiProvider enum value', function () {
        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'invalid-provider',
            'summary_language' => 'fr',
        ])->assertInvalid(['provider']);
    });

    it('validates summary_language is a valid locale', function () {
        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'anthropic',
            'summary_language' => 'de',
        ])->assertInvalid(['summary_language']);
    });

    it('delegates POST /test to TestAiConnection and returns JSON', function () {
        TestAiConnection::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(true);

        $this->postJson(route('settings.ai.test'))
            ->assertSuccessful()
            ->assertJson(['success' => true]);
    });

    it('returns success false when TestAiConnection fails', function () {
        TestAiConnection::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(false);

        $this->postJson(route('settings.ai.test'))
            ->assertSuccessful()
            ->assertJson(['success' => false]);
    });
})->group('controllers');

describe('UpdateAiSettings action', function () {
    it('persists ai settings', function () {
        UpdateAiSettings::make()->handle([
            'enabled' => true,
            'provider' => 'anthropic',
            'api_key' => 'sk-test-key',
            'summary_language' => 'en',
        ]);

        $settings = app(AiSettings::class);
        expect($settings->enabled)->toBeTrue()
            ->and($settings->provider)->toBe(AiProvider::Anthropic)
            ->and($settings->api_key)->toBe('sk-test-key')
            ->and($settings->summary_language)->toBe('en');
    });

    it('converts empty api_key to null', function () {
        UpdateAiSettings::make()->handle([
            'enabled' => false,
            'provider' => 'anthropic',
            'api_key' => '',
            'summary_language' => 'fr',
        ]);

        expect(app(AiSettings::class)->api_key)->toBeNull();
    });
})->group('actions');
