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
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class ScanAllSources extends Action
{
    /**
     * @param  array<int, ActivitySource>|null  $sources  Null = auto-discover from App\Services\ActivitySources
     */
    public function __construct(
        protected RecordActivityEvent $recordEvent,
        protected ?array $sources = null,
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

        return collect($this->sources ?? $this->discoverSources())
            ->filter(fn (ActivitySource $source) => $source->isAvailable())
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
     * @return array<int, ActivitySource>
     */
    public function discoverSources(?string $path = null): array
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
            ->values()
            ->all();
    }
}
