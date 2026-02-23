<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ProjectRepository;
use App\Models\Session;
use Carbon\CarbonImmutable;

class ActivityEventData
{
    public function __construct(
        public readonly ActivityEventSourceType $sourceType,
        public readonly ActivityEventType $type,
        public readonly CarbonImmutable $occurredAt,
        public readonly ProjectRepository $projectRepository,
        public readonly ?Session $session = null,
        public readonly array $metadata = [],
    ) {}
}
