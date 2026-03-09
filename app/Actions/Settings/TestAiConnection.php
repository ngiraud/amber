<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Ai\Agents\ReportSummarizer;
use App\Settings\AiSettings;
use Throwable;

class TestAiConnection extends Action
{
    public function __construct(
        protected readonly AiSettings $settings,
        protected readonly ReportSummarizer $agent,
    ) {}

    public function handle(): bool
    {
        try {
            $this->agent->prompt(
                'Say "ok" and nothing else.',
                provider: $this->settings->provider?->value,
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
