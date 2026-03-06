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

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            projects_path: (string) ($data['projects_path'] ?? '~/.claude/projects'),
        );
    }

    public static function validationRules(): array
    {
        return [
            'enabled' => ['boolean'],
            'projects_path' => ['string', 'max:500'],
        ];
    }

    public static function defaultData(): array
    {
        return [
            'enabled' => true,
            'projects_path' => '~/.claude/projects',
        ];
    }

    public static function fieldDefinitions(): array
    {
        return [
            new FieldDefinition(
                name: 'projects_path',
                type: 'text',
                label: 'Projects path',
                hint: 'Path to your Claude Code projects directory (default: ~/.claude/projects)',
                placeholder: '~/.claude/projects',
            ),
        ];
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
