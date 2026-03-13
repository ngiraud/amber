<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\ActivityEventSourceType;
use App\Http\Resources\ActivityEventResource;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;
use LogicException;

class EventsViewModel implements ProvidesInertiaProperties
{
    public function toInertiaProperties(RenderContext $context): iterable
    {
        $sinceOccurredAt = $this->parseSinceOccurredAt($context);

        if ($context->request->filled('since_occurred_at') && $sinceOccurredAt === null) {
            return [];
        }

        return [
            'events' => Inertia::scroll(
                fn () => ActivityEventResource::collection(
                    $this->getQuery($context)
                        ->with($this->getRelations($context))
                        ->latest('occurred_at')
                        ->cursorPaginate()
                )
            ),
            'hasNewEvents' => fn () => $sinceOccurredAt !== null
                                       && $this->getQuery($context)
                                           ->where('occurred_at', '>', $sinceOccurredAt)
                                           ->exists(),
            'hasEnabledSources' => ActivityEventSourceType::collect()->some->isEnabled(),
        ];
    }

    protected function getQuery(RenderContext $context): Builder
    {
        $model = $this->getModel($context);

        return is_null($model) ? ActivityEvent::query() : $model->activityEvents();
    }

    protected function getModel(RenderContext $context): null|Project|Client|Session
    {
        if ($context->request->routeIs('projects.show')) {
            return $context->request->route('project');
        }

        if ($context->request->routeIs('clients.show')) {
            return $context->request->route('client');
        }

        if ($context->request->routeIs('sessions.show')) {
            return $context->request->route('session');
        }

        if (! $context->request->routeIs('activity.index')) {
            throw new LogicException('EventsViewModel used on an unsupported route: '.$context->request->path());
        }

        return null;
    }

    /** @return string[] */
    protected function getRelations(RenderContext $context): array
    {
        if ($context->request->routeIs('clients.show')) {
            return ['project', 'projectRepository'];
        }

        return ['projectRepository'];
    }

    private function parseSinceOccurredAt(RenderContext $context): ?Carbon
    {
        if (! $context->request->filled('since_occurred_at')) {
            return null;
        }

        $validator = Validator::make(
            ['since_occurred_at' => $context->request->integer('since_occurred_at')],
            ['since_occurred_at' => ['date_format:U']]
        );

        if ($validator->fails()) {
            return null;
        }

        return Carbon::createFromTimestamp($context->request->integer('since_occurred_at'));
    }
}
