<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\RoundingStrategy;
use App\Models\Client;
use App\Settings\GeneralSettings;
use Illuminate\Support\Collection;

class ProjectData
{
    /**
     * @param  Collection<int, ProjectRepositoryData>  $repositories
     */
    public function __construct(
        public readonly Client $client,
        public readonly string $name,
        public readonly string $color,
        public readonly RoundingStrategy $rounding,
        public readonly int $daily_reference_hours = 8,
        public readonly bool $is_active = true,
        public readonly ?float $hourly_rate = null,
        public readonly ?float $daily_rate = null,
        public readonly Collection $repositories = new Collection,
    ) {}

    public static function fromArray(array $data): self
    {
        $settings = app(GeneralSettings::class);

        return new self(
            client: $data['client_id'] instanceof Client ? $data['client_id'] : Client::findOrFail($data['client_id']),
            name: $data['name'],
            color: $data['color'],
            rounding: isset($data['rounding']) ? RoundingStrategy::from((int) $data['rounding']) : $settings->default_rounding_strategy,
            daily_reference_hours: (int) ($data['daily_reference_hours'] ?? $settings->default_daily_reference_hours),
            is_active: (bool) ($data['is_active'] ?? true),
            hourly_rate: isset($data['hourly_rate']) ? (float) $data['hourly_rate'] : $settings->default_hourly_rate,
            daily_rate: isset($data['daily_rate']) ? (float) $data['daily_rate'] : $settings->default_daily_rate,
            repositories: collect($data['repositories'] ?? [])->map(fn (array $repo) => ProjectRepositoryData::fromArray($repo)),
        );
    }

    /**
     * @return array<string, mixed>
     */
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
