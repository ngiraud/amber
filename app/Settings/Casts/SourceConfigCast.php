<?php

declare(strict_types=1);

namespace App\Settings\Casts;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class SourceConfigCast implements SettingsCast
{
    /**
     * @param  class-string<SourceConfig>  $class
     */
    public function __construct(private readonly string $class) {}

    public function get(mixed $payload): SourceConfig
    {
        return $this->class::fromArray(is_array($payload) ? $payload : []);
    }

    public function set(mixed $payload): array
    {
        return $payload instanceof SourceConfig ? $payload->toArray() : [];
    }
}
