<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;

class ClaudeCodeSourceConfig implements SourceConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $projects_path,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            enabled: (bool) ($data['enabled'] ?? true),
            projects_path: (string) ($data['projects_path'] ?? '~/.claude/projects'),
        );
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'projects_path' => $this->projects_path,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
