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

    public static function validationRules(): array
    {
        return [
            'enabled' => ['boolean'],
            'debounce_seconds' => ['integer', 'min:1', 'max:30'],
            'excluded_patterns' => ['array'],
            'excluded_patterns.*' => ['string'],
            'allowed_extensions' => ['array'],
            'allowed_extensions.*' => ['string'],
        ];
    }

    public static function defaultData(): array
    {
        return [
            'enabled' => true,
            'debounce_seconds' => 3,
            'excluded_patterns' => [
                '\.git/',
                '\.idea/',
                'node_modules/',
                'vendor/',
                '\.DS_Store',
                'storage/',
                '\.php-cs-fixer\.cache',
                '\.sqlite',
                '\.cache',
            ],
            'allowed_extensions' => [
                'php', 'js', 'ts', 'vue', 'jsx', 'tsx',
                'css', 'scss', 'sass', 'less',
                'html', 'blade.php',
                'json', 'yaml', 'yml', 'toml', 'env',
                'md', 'mdx',
                'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift',
                'sh', 'bash', 'zsh',
                'sql',
            ],
        ];
    }

    public static function fieldDefinitions(): array
    {
        return [
            new FieldDefinition(
                name: 'debounce_seconds',
                type: 'number',
                label: 'Debounce (seconds)',
                hint: 'Delay before recording a file change event',
                min: 1,
                max: 30,
            ),
            new FieldDefinition(
                name: 'excluded_patterns',
                type: 'string-list',
                label: 'Excluded patterns',
                hint: 'One regex pattern per line — matching paths are ignored',
                rows: 6,
                separator: "\n",
            ),
            new FieldDefinition(
                name: 'allowed_extensions',
                type: 'string-list',
                label: 'Allowed extensions',
                hint: 'Comma-separated extensions to track without dot (e.g. php, ts, vue)',
                rows: 3,
                separator: ',',
            ),
        ];
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
