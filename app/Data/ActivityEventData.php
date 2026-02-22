<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ActivityEventType;
use Carbon\CarbonImmutable;

class ActivityEventData
{
    public function __construct(
        public readonly ActivityEventType $type,
        public readonly string $sourceType,
        public readonly CarbonImmutable $occurredAt,
        public readonly array $metadata = [],
        public readonly ?string $projectId = null,
        public readonly ?string $projectRepositoryId = null,
        public readonly ?string $filePath = null,
    ) {}
}
