<?php

declare(strict_types=1);

namespace App\Data;

class ProjectData
{
    public function __construct(
    ) {}

    public static function fromArray(array $data): self
    {
        return new self;
    }

    public function toArray(): array
    {
        return [
        ];
    }
}
