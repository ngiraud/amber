<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs\Contracts;

interface SourceConfig
{
    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self;

    /** @return array<string, mixed> */
    public function toArray(): array;

    public function isEnabled(): bool;
}
