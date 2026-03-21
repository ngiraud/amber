<?php

declare(strict_types=1);

use App\Actions\Settings\TestAiConnection;
use App\Actions\Settings\UpdateAiSettings;
use App\Ai\Agents\ReportSummarizer;
use App\Enums\AiProvider;
use App\Exceptions\AiConnectionException;
use App\Settings\AiSettings;

pest()->group('settings', 'ai');

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

    it('delegates PUT to UpdateAiSettings and redirects back', function () {
        UpdateAiSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['enabled'] === true && $data['provider'] === 'anthropic'));

        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'anthropic',
            'api_key' => 'sk-test',
            'summary_language' => 'fr',
        ])->assertRedirectBack();
    });

    it('validates provider is a valid AiProvider enum value', function () {
        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'invalid-provider',
            'summary_language' => 'fr',
        ])->assertInvalid(['provider']);
    });

    it('requires provider when enabled', function () {
        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => null,
            'summary_language' => 'fr',
        ])->assertInvalid(['provider']);
    });

    it('does not require provider when disabled', function () {
        UpdateAiSettings::fake()->shouldReceive('handle')->once();

        $this->put(route('settings.ai'), [
            'enabled' => false,
            'provider' => null,
            'summary_language' => 'fr',
        ])->assertValid(['provider']);
    });

    it('validates summary_language is a valid locale', function () {
        $this->put(route('settings.ai'), [
            'enabled' => true,
            'provider' => 'anthropic',
            'summary_language' => 'invalid-locale',
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

    it('returns a JSON error when TestAiConnection throws AiConnectionException', function () {
        TestAiConnection::fake()
            ->shouldReceive('handle')
            ->once()
            ->andThrow(new AiConnectionException('Invalid API key. Please check your AI settings.'));

        $this->postJson(route('settings.ai.test'))
            ->assertStatus(422)
            ->assertJson(['error' => 'Invalid API key. Please check your AI settings.']);
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

    it('sets the provider api key in runtime config when handle is called', function () {
        $settings = app(AiSettings::class);
        $settings->provider = AiProvider::Anthropic;
        $settings->api_key = 'sk-test-key';
        $settings->save();

        ReportSummarizer::fake(fn () => true);

        try {
            TestAiConnection::make()->handle();
        } catch (AiConnectionException) {
            // The key is set before the prompt call; ignore connection failures in this test
        }

        expect(config('ai.providers.anthropic.key'))->toBe('sk-test-key');
    });

    it('clears api_key when provider is null', function () {
        UpdateAiSettings::make()->handle([
            'enabled' => false,
            'provider' => null,
            'api_key' => 'sk-test-key',
            'summary_language' => 'fr',
        ]);

        expect(app(AiSettings::class)->api_key)->toBeNull();
    });
})->group('actions');
