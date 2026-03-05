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
