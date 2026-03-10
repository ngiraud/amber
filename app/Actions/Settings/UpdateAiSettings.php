<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Enums\AiProvider;
use App\Settings\AiSettings;

class UpdateAiSettings extends Action
{
    public function __construct(protected readonly AiSettings $settings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        $this->settings->enabled = (bool) ($data['enabled'] ?? false);

        if (isset($data['provider'])) {
            $this->settings->provider = AiProvider::from($data['provider']);
        }

        if (isset($data['api_key'])) {
            $this->settings->api_key = $data['api_key'] ?: null;
        }

        if (! $this->settings->provider->requiresApiKey()) {
            $this->settings->api_key = null;
        }

        if (isset($data['summary_language'])) {
            $this->settings->summary_language = $data['summary_language'];
        }

        $this->settings->save();
    }
}
