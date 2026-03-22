<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Http\Resources\ProjectResource;
use App\Models\ActivityEvent;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

class CurrentActivityViewModel implements ProvidesInertiaProperties
{
    /**
     * @return array<string, mixed>
     */
    public function toInertiaProperties(RenderContext $context): array
    {
        return [
            'currentActivity' => fn () => $this->resolve(),
        ];
    }

    /**
     * @return list<array{project: ProjectResource, since: string}>|null
     */
    private function resolve(): ?array
    {
        $cutoff = CarbonImmutable::now()->subMinutes(config('activity.current_activity_timeout_minutes'));

        $groups = DB::table((new ActivityEvent)->getTable())
            ->selectRaw('project_id, MIN(occurred_at) as since')
            ->where('occurred_at', '>=', $cutoff)
            ->groupBy('project_id')
            ->get();

        if ($groups->isEmpty()) {
            return null;
        }

        $projects = Project::query()
            ->whereIn('id', $groups->pluck('project_id'))
            ->with('client')
            ->get();

        return $groups
            ->map(fn (object $row) => [
                'project' => ProjectResource::make($projects->firstWhere('id', $row->project_id)),
                'since' => CarbonImmutable::parse($row->since)->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
