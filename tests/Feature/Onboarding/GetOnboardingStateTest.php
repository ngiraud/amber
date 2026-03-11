<?php

declare(strict_types=1);

use App\Actions\Onboarding\GetOnboardingState;
use App\Enums\ActivityEventSourceType;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use App\Settings\ActivitySourceSettings;
use App\Settings\AiSettings;
use App\Settings\GeneralSettings;

pest()->group('onboarding', 'actions');

describe('get onboarding state', function () {
    it('returns all steps incomplete by default', function () {
        $state = app(GetOnboardingState::class)->handle();

        expect($state['dismissed'])->toBeFalse()
            ->and($state['all_complete'])->toBeFalse()
            ->and($state['steps'])->toHaveCount(6);

        foreach ($state['steps'] as $step) {
            if ($step['optional']) {
                continue;
            }
            expect($step['complete'])->toBeFalse("Step '{$step['key']}' should be incomplete");
        }
    });

    it('marks company step complete when company name is set', function () {
        $settings = app(GeneralSettings::class);
        $settings->company_name = 'Acme Corp';
        $settings->save();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'company');

        expect($step['complete'])->toBeTrue();
    });

    it('marks client step complete when a client exists', function () {
        Client::factory()->create();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'new-client');

        expect($step['complete'])->toBeTrue();
    });

    it('marks project step complete when a project has a repository', function () {
        $project = Project::factory()->create();
        ProjectRepository::factory()->for($project)->create();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'new-project');

        expect($step['complete'])->toBeTrue();
    });

    it('does not mark project step complete when project has no repository', function () {
        Project::factory()->create();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'new-project');

        expect($step['complete'])->toBeFalse();
    });

    it('marks ai step complete when AI is enabled', function () {
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->save();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'ai');

        expect($step['complete'])->toBeTrue();
        expect($step['optional'])->toBeTrue();
    });

    it('marks session step complete when a session exists', function () {
        Session::factory()->create();

        $state = app(GetOnboardingState::class)->handle();
        $step = collect($state['steps'])->firstWhere('key', 'start-session');

        expect($step['complete'])->toBeTrue();
    });

    it('considers all_complete true when all required steps are done', function () {
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->company_name = 'Acme Corp';
        $generalSettings->save();

        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();
        ProjectRepository::factory()->for($project)->create();
        Session::factory()->create();

        $sourceSettings = app(ActivitySourceSettings::class);
        $sourceSettings->setConfig(ActivityEventSourceType::Git, ['enabled' => true, 'author_emails' => []]);
        $sourceSettings->save();

        $state = app(GetOnboardingState::class)->handle();

        expect($state['all_complete'])->toBeTrue();
    });

    it('returns dismissed flag from settings', function () {
        $settings = app(GeneralSettings::class);
        $settings->onboarding_dismissed = true;
        $settings->save();

        $state = app(GetOnboardingState::class)->handle();

        expect($state['dismissed'])->toBeTrue();
    });
});
