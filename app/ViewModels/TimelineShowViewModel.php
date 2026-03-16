<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Http\Resources\SessionResource;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

class TimelineShowViewModel implements ProvidesInertiaProperties
{
    public function toInertiaProperties(RenderContext $context): array
    {
        $day = CarbonImmutable::parse($context->request->route('date'));

        $sessions = Session::query()
            ->with('project.client')
            ->where('date', $day)
            ->orderBy('started_at')
            ->get();

        return [
            'date' => $day->toDateString(),
            'previous_date' => $day->subDay()->toDateString(),
            'next_date' => $day->addDay()->toDateString(),
            'sessions' => SessionResource::collection($sessions),
            'session_stats' => new SessionStatsViewModel($sessions->whereNotNull('ended_at')->values()),
        ];
    }
}
