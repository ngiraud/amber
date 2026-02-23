<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Contracts\ActivitySource;
use App\Data\ActivityEventData;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ScanAllSources extends Action
{
    public function __construct(
        protected RecordActivityEvent $recordEvent,
    ) {}

    /**
     * @return Collection<int, ActivityEvent>
     */
    public function handle(CarbonImmutable $since): Collection
    {
        $repos = ProjectRepository::query()
            ->forActiveProjects()
            ->get();

        $activeSession = Session::findActive();

        Log::channel('activity')->info('[activity:scan] Context', [
            'active_repos' => $repos->count(),
            'active_session' => $activeSession?->id,
        ]);

        $sources = $this->discoverSources();

        Log::channel('activity')->info('[activity:scan] Available sources', [
            'sources' => $sources->map->identifier()->values()->all(),
        ]);

        return $sources
            ->flatMap(fn (ActivitySource $source) => $source->scan($since, $repos))
            ->unique(fn (ActivityEventData $data) => implode('|', [
                $data->type->value,
                $data->occurredAt->toIso8601String(),
                $data->sourceType,
            ]))
            ->map(fn (ActivityEventData $data) => $this->recordEvent->handle($data, $activeSession))
            ->filter()
            ->values();
    }

    /**
     * Discover all concrete ActivitySource implementations under the given path.
     *
     * @return Collection<int, ActivitySource>
     */
    public function discoverSources(?string $path = null): Collection
    {
        $path ??= app_path('Services/ActivitySources');
        $namespace = app()->getNamespace();

        return collect((new Finder)->in($path)->files()->name('*.php'))
            ->map(fn ($file) => $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
            ))
            ->filter(fn (string $class) => class_exists($class)
                && is_subclass_of($class, ActivitySource::class)
                && ! (new ReflectionClass($class))->isAbstract()
            )
            ->map(fn (string $class) => app($class))
            ->filter(fn (ActivitySource $source) => $source->isAvailable())
            ->values();
    }
}
