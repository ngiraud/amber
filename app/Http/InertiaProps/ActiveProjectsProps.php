<?php

declare(strict_types=1);

namespace App\Http\InertiaProps;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

class ActiveProjectsProps implements ProvidesInertiaProperties
{
    /**
     * @return array<string, mixed>
     */
    public function toInertiaProperties(RenderContext $context): array
    {
        return [
            'projects' => Inertia::optional(fn () => ProjectResource::collection(
                Project::active()
                    ->with('client')
                    ->withMax('sessions', 'started_at')
                    ->orderByDesc('sessions_max_started_at')
                    ->orderBy('name')
                    ->get()
            )),
        ];
    }
}
