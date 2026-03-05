<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;

class FswatchSourceConfig implements SourceConfig
{
    /**
     * @param  string[]  $excluded_patterns
     * @param  string[]  $allowed_extensions
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly int $debounce_seconds,
        public readonly array $excluded_patterns,
        public readonly array $allowed_extensions,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            debounce_seconds: (int) ($data['debounce_seconds'] ?? 3),
            excluded_patterns: (array) ($data['excluded_patterns'] ?? []),
            allowed_extensions: (array) ($data['allowed_extensions'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'debounce_seconds' => $this->debounce_seconds,
            'excluded_patterns' => $this->excluded_patterns,
            'allowed_extensions' => $this->allowed_extensions,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
