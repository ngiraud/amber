<?php

declare(strict_types=1);

namespace App\Actions\Onboarding;

use App\Actions\Action;
use App\Enums\ActivityEventSourceType;
use App\Models\Client;
use App\Models\Project;
use App\Models\Session;
use App\Settings\ActivitySourceSettings;
use App\Settings\AiSettings;
use App\Settings\GeneralSettings;

class GetOnboardingState extends Action
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly AiSettings $aiSettings,
        private readonly ActivitySourceSettings $sourceSettings,
    ) {}

    /**
     * @return array{
     *   dismissed: bool,
     *   all_complete?: bool,
     *   steps?: array<int, array{key: string, label: string, description: string, complete: bool, url: string, optional: bool}>
     * }
     */
    public function handle(): array
    {
        if ($this->generalSettings->onboarding_dismissed) {
            return [
                'dismissed' => $this->generalSettings->onboarding_dismissed,
            ];
        }

        $steps = $this->buildSteps();

        $requiredSteps = array_filter($steps, fn (array $step): bool => ! $step['optional']);
        $allComplete = count(array_filter($requiredSteps, fn (array $step): bool => $step['complete'])) === count($requiredSteps);

        if ($allComplete) {
            $this->generalSettings->onboarding_dismissed = true;
            $this->generalSettings->save();
        }

        return [
            'dismissed' => $this->generalSettings->onboarding_dismissed,
            'all_complete' => $allComplete,
            'steps' => array_values($steps),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, complete: bool, url: string, optional: bool}>
     */
    private function buildSteps(): array
    {
        return [
            [
                'key' => 'company',
                'label' => 'Set up company info',
                'description' => 'Add your company name to personalize the app.',
                'complete' => ! empty($this->generalSettings->company_name),
                'url' => '/settings/general?spotlight=company',
                'optional' => false,
            ],
            [
                'key' => 'sources',
                'label' => 'Configure activity sources',
                'description' => 'Enable the sources that match your workflow.',
                'complete' => $this->hasConfiguredSource(),
                'url' => '/settings/sources?spotlight=sources',
                'optional' => false,
            ],
            [
                'key' => 'ai',
                'label' => 'Configure AI summaries',
                'description' => 'Optionally enable AI to generate activity report descriptions.',
                'complete' => $this->aiSettings->enabled,
                'url' => '/settings/ai?spotlight=ai',
                'optional' => true,
            ],
            [
                'key' => 'new-client',
                'label' => 'Create your first client',
                'description' => 'Clients group your projects and appear on invoices.',
                'complete' => Client::exists(),
                'url' => '/clients?spotlight=new-client',
                'optional' => false,
            ],
            [
                'key' => 'new-project',
                'label' => 'Create a project with a repository',
                'description' => 'Link a git repository to automatically track your work.',
                'complete' => Project::query()->whereHas('repositories')->exists(),
                'url' => '/projects?spotlight=new-project',
                'optional' => false,
            ],
            [
                'key' => 'start-session',
                'label' => 'Start your first session',
                'description' => 'Track your first work session to see everything in action.',
                'complete' => Session::exists(),
                'url' => '/?spotlight=start-session',
                'optional' => false,
            ],
        ];
    }

    private function hasConfiguredSource(): bool
    {
        return array_any(
            ActivityEventSourceType::cases(),
            fn (ActivityEventSourceType $type) => $this->sourceSettings->configFor($type)->isEnabled()
        );
    }
}
