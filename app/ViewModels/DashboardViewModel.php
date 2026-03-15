<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Actions\Onboarding\GetOnboardingState;
use App\Http\Resources\SessionResource;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

class DashboardViewModel implements ProvidesInertiaProperties
{
    public function __construct(protected GetOnboardingState $getOnboardingState) {}

    public function toInertiaProperties(RenderContext $context): array
    {
        $today = CarbonImmutable::today();

        $sessions = Session::query()
            ->with('project.client')
            ->whereNotNull('ended_at')
            ->whereDate('date', $today)
            ->orderBy('started_at')
            ->get();

        $weekMinutes = (int) Session::query()
            ->whereNotNull('ended_at')
            ->whereBetween('date', [$today->startOfWeek()->toDateString(), $today->toDateString()])
            ->sum('rounded_minutes');

        $monthMinutes = (int) Session::query()
            ->whereNotNull('ended_at')
            ->whereBetween('date', [$today->startOfMonth()->toDateString(), $today->toDateString()])
            ->sum('rounded_minutes');

        return [
            'date' => $today->toDateString(),
            'sessions' => SessionResource::collection($sessions),
            'total_minutes' => $sessions->sum('rounded_minutes'),
            'week_minutes' => $weekMinutes,
            'month_minutes' => $monthMinutes,
            'onboarding' => fn () => $this->getOnboardingState->handle(),
        ];
    }
}
