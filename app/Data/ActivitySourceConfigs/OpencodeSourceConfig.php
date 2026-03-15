<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;

class OpencodeSourceConfig implements SourceConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $projects_path,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            projects_path: (string) ($data['projects_path'] ?? '~/.local/share/opencode'),
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
            'projects_path' => '~/.local/share/opencode',
        ];
    }

    public static function fieldDefinitions(): array
    {
        return [
            new FieldDefinition(
                name: 'projects_path',
                type: 'folder-path',
                label: 'Projects path',
                hint: 'Path to your Opencode data directory (default: ~/.local/share/opencode)',
                placeholder: '~/.local/share/opencode',
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
