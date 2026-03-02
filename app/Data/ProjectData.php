<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\RoundingStrategy;
use App\Models\Client;

class ProjectData
{
    public function __construct(
        public readonly Client $client,
        public readonly string $name,
        public readonly string $color,
        public readonly RoundingStrategy $rounding,
        public readonly int $daily_reference_hours = 7,
        public readonly bool $is_active = true,
        public readonly ?float $hourly_rate = null,
        public readonly ?float $daily_rate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            client: $data['client_id'] instanceof Client ? $data['client_id'] : Client::findOrFail($data['client_id']),
            name: $data['name'],
            color: $data['color'],
            rounding: RoundingStrategy::from((int) $data['rounding']),
            daily_reference_hours: (int) ($data['daily_reference_hours'] ?? 7),
            is_active: (bool) ($data['is_active'] ?? true),
            hourly_rate: isset($data['hourly_rate']) ? (float) $data['hourly_rate'] : null,
            daily_rate: isset($data['daily_rate']) ? (float) $data['daily_rate'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->client->id,
            'name' => $this->name,
            'color' => $this->color,
            'rounding' => $this->rounding,
            'daily_reference_hours' => $this->daily_reference_hours,
            'is_active' => $this->is_active,
            'hourly_rate' => $this->hourly_rate,
            'daily_rate' => $this->daily_rate,
        ];
    }
}
