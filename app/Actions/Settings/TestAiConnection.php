<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Ai\Agents\ReportSummarizer;
use App\Exceptions\AiConnectionException;
use App\Settings\AiSettings;
use Laravel\Ai\Exceptions\AiException;
use Throwable;

class TestAiConnection extends Action
{
    public function __construct(
        protected readonly AiSettings $settings,
        protected readonly ReportSummarizer $agent,
    ) {}

    public function handle(): true
    {
        $this->settings->syncConfigApiKey();

        try {
            $this->agent->prompt(
                'Say "ok" and nothing else.',
                provider: $this->settings->provider?->value,
            );

            return true;
        } catch (AiException $e) {
            throw AiConnectionException::fromAiException($e);
        } catch (Throwable $e) {
            throw AiConnectionException::fromUnexpected($e);
        }
    }
}
