<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

class ClientData
{
    public function __construct(
        public readonly string $name,
        public readonly ?Collection $address = null,
        public readonly ?Collection $contacts = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            address: isset($data['address']) ? Collection::wrap($data['address']) : null,
            contacts: isset($data['contacts']) ? Collection::wrap($data['contacts']) : null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'address' => $this->address?->toArray(),
            'contacts' => $this->contacts?->toArray(),
            'notes' => $this->notes,
        ];
    }
}
