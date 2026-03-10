<?php

declare(strict_types=1);

namespace App\Settings;

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

    /**
     * @return string[]
     */
    public static function encrypted(): array
    {
        return [
            'api_key',
        ];
    }
}
