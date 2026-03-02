<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

class SessionData
{
    public function __construct(
        public readonly ?CarbonImmutable $startedAt = null,
        public readonly ?CarbonImmutable $endedAt = null,
        public readonly ?string $description = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startedAt: isset($data['started_at']) ? CarbonImmutable::parse($data['started_at']) : null,
            endedAt: isset($data['ended_at']) ? CarbonImmutable::parse($data['ended_at']) : null,
            description: $data['description'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
