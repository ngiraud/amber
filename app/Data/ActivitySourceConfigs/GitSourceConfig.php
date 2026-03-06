<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;

class GitSourceConfig implements SourceConfig
{
    /** @param  string[]  $author_emails */
    public function __construct(
        public readonly bool $enabled,
        public readonly array $author_emails,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            author_emails: (array) ($data['author_emails'] ?? []),
        );
    }

    public static function validationRules(): array
    {
        return [
            'enabled' => 'boolean',
            'author_emails' => 'array',
            'author_emails.*' => 'email',
        ];
    }

    public static function defaultData(): array
    {
        return [
            'enabled' => true,
            'author_emails' => [],
        ];
    }

    public static function fieldDefinitions(): array
    {
        return [
            new FieldDefinition(
                name: 'author_emails',
                type: 'email-list',
                label: 'Author Emails',
                hint: 'Comma-separated emails used in git commits',
                placeholder: 'me@example.com, work@example.com',
                separator: ',',
            ),
        ];
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'author_emails' => $this->author_emails,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
