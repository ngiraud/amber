<?php

declare(strict_types=1);

namespace App\Actions\Onboarding;

use App\Actions\Action;
use App\Settings\GeneralSettings;

class DismissOnboarding extends Action
{
    public function __construct(
        private readonly GeneralSettings $generalSettings,
    ) {}

    public function handle(): void
    {
        $this->generalSettings->onboarding_dismissed = true;
        $this->generalSettings->save();
    }
}
