<?php

declare(strict_types=1);

namespace App\Data;

class ActivityReportData
{
    public function __construct(
        public readonly string $clientId,
        public readonly int $month,
        public readonly int $year,
        public readonly ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'],
            month: (int) $data['month'],
            year: (int) $data['year'],
            notes: $data['notes'] ?? null,
        );
    }
}
