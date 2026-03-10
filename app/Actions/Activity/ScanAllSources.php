<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Data\ActivityEventData;
use App\Enums\ActivityEventSourceType;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Models\Session;
use App\Services\ActivitySources\Contracts\ActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ScanAllSources extends Action
{
    /**
     * @return Collection<int, ActivityEvent>
     */
    public function handle(CarbonImmutable $since, ?ActivityEventSourceType $sourceType = null): Collection
    {
        $repos = ProjectRepository::query()
            ->forActiveProjects()
            ->get();

        $activeSession = Session::findActive();

        $recordEventAction = app(RecordActivityEvent::class);

        return $this->discoverSources($sourceType)
            ->flatMap(fn (ActivitySource $source) => $source->scan($since, $repos))
            ->unique(fn (ActivityEventData $data) => implode('|', [
                $data->sourceType->value,
                $data->type->value,
                $data->occurredAt->toIso8601String(),
            ]))
            ->map(fn (ActivityEventData $data) => $recordEventAction->handle($data, $activeSession))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, ActivitySource>
     */
    public function discoverSources(?ActivityEventSourceType $sourceType = null): Collection
    {
        /** @var Collection<int, ActivityEventSourceType> $types */
        $types = $sourceType ? collect([$sourceType]) : ActivityEventSourceType::collect();

        return $types
            ->filter(fn (ActivityEventSourceType $type) => $type->isEnabled())
            ->map(fn (ActivityEventSourceType $type) => $type->sourceClass())
            ->filter()
            ->map(fn (string $class) => app($class))
            ->filter(fn (ActivitySource $source) => $source->isAvailable())
            ->values();
    }
}
