<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Data\ActivityEventData;
use App\Data\ScanActivityResult;
use App\Enums\ActivityEventSourceType;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Models\Session;
use App\Services\ActivitySources\Contracts\ActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

class ScanActivitySources extends Action
{
    private CarbonImmutable $since;

    private CarbonImmutable $until;

    /** @var Collection<int, ProjectRepository> */
    private Collection $repositories;

    private ?Session $activeSession;

    /** @var Collection<int, string> */
    private Collection $errors;

    /**
     * @param  Collection<int, ActivityEventSourceType>|null  $sources  Sources to scan, or null to scan all enabled sources
     */
    public function handle(CarbonImmutable $since, CarbonImmutable $until, ?Collection $sources = null): ScanActivityResult
    {
        $this->since = $since;
        $this->until = $until;
        $this->repositories = ProjectRepository::query()->forActiveProjects()->get();
        $this->activeSession = Session::findActive();
        $this->errors = collect();

        $recorded = $this->discoverSources($sources)
            ->pipe($this->scanEachSource(...))
            ->pipe($this->deduplicateEvents(...))
            ->pipe($this->recordEvents(...));

        return new ScanActivityResult(events: $recorded, errors: $this->errors);
    }

    /**
     * @param  Collection<int, ActivityEventSourceType>|null  $sources
     * @return Collection<int, ActivitySource>
     */
    public function discoverSources(?Collection $sources = null): Collection
    {
        /** @var Collection<int, ActivityEventSourceType> $types */
        $types = $sources ?? ActivityEventSourceType::collect();

        return $types
            ->filter(fn (ActivityEventSourceType $type) => $type->isEnabled())
            ->map(fn (ActivityEventSourceType $type) => $type->sourceClass())
            ->filter()
            ->map(fn (string $class) => app($class))
            ->filter(fn (ActivitySource $source) => $source->isAvailable())
            ->values();
    }

    /**
     * @param  Collection<int, ActivitySource>  $sources
     * @return Collection<int, ActivityEventData>
     */
    protected function scanEachSource(Collection $sources): Collection
    {
        return $sources->flatMap(function (ActivitySource $source): Collection {
            try {
                return $source->scan($this->since, $this->until, $this->repositories);
            } catch (Throwable $e) {
                $this->errors->push("{$source->identifier()->label()}: {$e->getMessage()}");

                return collect();
            }
        });
    }

    /**
     * @param  Collection<int, ActivityEventData>  $events
     * @return Collection<int, ActivityEventData>
     */
    protected function deduplicateEvents(Collection $events): Collection
    {
        return $events->unique(fn (ActivityEventData $data) => implode('|', [
            $data->sourceType->value,
            $data->type->value,
            $data->occurredAt->toIso8601String(),
        ]));
    }

    /**
     * @param  Collection<int, ActivityEventData>  $events
     * @return Collection<int, ActivityEvent>
     */
    protected function recordEvents(Collection $events): Collection
    {
        $recordEventAction = app(RecordActivityEvent::class);

        return $events
            ->map(fn (ActivityEventData $data) => $recordEventAction->handle($data, $this->activeSession))
            ->filter()
            ->values();
    }
}
