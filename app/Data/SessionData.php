<?php

declare(strict_types=1);

namespace App\Data;

class SessionData
{
    public function __construct(
        public readonly string $project_id,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            project_id: $data['project_id'],
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->project_id,
            'notes' => $this->notes,
        ];
    }
}
