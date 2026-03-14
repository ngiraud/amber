<?php

declare(strict_types=1);

namespace App\Settings;

use App\Casts\NativeEncryptCast;
use App\Enums\AiProvider;
use Spatie\LaravelSettings\Settings;

class AiSettings extends Settings
{
    public bool $enabled;

    public ?AiProvider $provider;

    public ?string $api_key;

    public string $summary_language;

    public static function group(): string
    {
        return 'ai';
    }

    public static function casts(): array
    {
        return [
            'api_key' => NativeEncryptCast::class,
        ];
    }

    /**
     * Sync the AI provider API key into the runtime config so the Laravel AI SDK can use it.
     * Safe to call at any point after the NativePHP Electron client is ready.
     */
    public function syncConfigApiKey(): self
    {
        if ($this->provider && ! empty($this->api_key)) {
            config(["ai.providers.{$this->provider->value}.key" => $this->api_key]);
        }

        return $this;
    }
}
