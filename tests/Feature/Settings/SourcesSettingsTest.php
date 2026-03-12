<?php

declare(strict_types=1);

use App\Actions\Settings\TestActivitySourceConnection;
use App\Actions\Settings\UpdateActivitySourceSettings;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Services\ActivitySources\ClaudeCodeActivitySource;
use App\Services\ActivitySources\GitActivitySource;
use App\Services\ActivitySources\GitHubActivitySource;
use App\Services\FileWatcherService;
use App\Settings\ActivitySourceSettings;
use Illuminate\Support\Facades\Process;

pest()->group('settings', 'sources');

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

    it('delegates PUT to UpdateActivitySourceSettings and redirects back', function () {
        UpdateActivitySourceSettings::fake()
            ->shouldReceive('handle')
            ->once();

        $this->put(route('settings.sources.update'), ['git' => ['enabled' => false, 'author_emails' => []]])
            ->assertRedirectBack();
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
