<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Session;
use Carbon\CarbonImmutable;
use Inertia\ProvidesInertiaProperties;
use Inertia\RenderContext;

class TimelineIndexViewModel implements ProvidesInertiaProperties
{
    public function toInertiaProperties(RenderContext $context): array
    {
        $year = $context->request->integer('year', now()->year);
        $month = $context->request->integer('month', now()->month);

        $startOfMonth = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->endOfMonth();

        $sessions = Session::query()
            ->with('project')
            ->whereNotNull('ended_at')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $sessionsByDate = $sessions->groupBy(fn (Session $s) => $s->date?->toDateString());

        $days = collect();
        $current = $startOfMonth;

        while ($current->lte($endOfMonth)) {
            $daySessions = $sessionsByDate->get($current->toDateString(), collect());

            $days->push([
                'date' => $current->toDateString(),
                'total_minutes' => $daySessions->sum('rounded_minutes'),
                'projects' => $daySessions
                    ->groupBy('project_id')
                    ->map(fn ($group) => [
                        'id' => $group->first()->project_id,
                        'name' => $group->first()->project?->name,
                        'color' => $group->first()->project?->color,
                        'minutes' => $group->sum('rounded_minutes'),
                    ])
                    ->values(),
            ]);

            $current = $current->addDay();
        }

        return [
            'year' => $year,
            'month' => $month,
            'days' => $days,
        ];
    }
}
