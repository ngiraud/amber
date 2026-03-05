<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;

class GitHubSourceConfig implements SourceConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly ?string $username,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            username: isset($data['username']) && $data['username'] !== '' ? (string) $data['username'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'username' => $this->username,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
