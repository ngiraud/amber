<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
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
            ->whereDate('date', '>=', $startOfMonth->toDateString())
            ->whereDate('date', '<=', $endOfMonth->toDateString())
            ->get();

        return [
            'year' => $year,
            'month' => $month,
            'days' => $this->buildDays($sessions, $startOfMonth, $endOfMonth),
            'stats' => $this->buildMonthStats($sessions, $year, $month),
            'weeks' => $this->buildWeekBreakdown($sessions, $startOfMonth, $endOfMonth),
        ];
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<int, array{date: string, total_minutes: int, projects: array<int, mixed>}>
     */
    private function buildDays(Collection $sessions, CarbonImmutable $startOfMonth, CarbonImmutable $endOfMonth): array
    {
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

        return $days->all();
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array{month_total_minutes: int, month_worked_days: int, month_avg_minutes_per_day: int, month_avg_minutes_per_week: int, current_week_total_minutes: int|null, month_project_breakdown: array<int, mixed>}
     */
    private function buildMonthStats(Collection $sessions, int $year, int $month): array
    {
        $total = (int) $sessions->sum('rounded_minutes');
        $workedDays = $sessions->groupBy(fn (Session $s) => $s->date?->toDateString())->count();
        $workedWeeks = $sessions->groupBy(fn (Session $s) => $s->date?->startOfWeek()->toDateString())->count();

        $today = now();
        $isCurrentMonth = $today->year === $year && $today->month === $month;

        $currentWeekTotal = $isCurrentMonth
            ? $sessions->whereBetween('date', [$today->startOfWeek(), $today->endOfWeek()])->sum('rounded_minutes')
            : null;

        return [
            'month_total_minutes' => $total,
            'month_worked_days' => $workedDays,
            'month_avg_minutes_per_day' => $workedDays > 0 ? (int) round($total / $workedDays) : 0,
            'month_avg_minutes_per_week' => $workedWeeks > 0 ? (int) round($total / $workedWeeks) : 0,
            'current_week_total_minutes' => $currentWeekTotal,
            'month_project_breakdown' => $this->buildProjectBreakdown($sessions, $total),
        ];
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<int, array{label: string, start_date: CarbonImmutable, end_date: CarbonImmutable, total_minutes: int, worked_days: int, avg_minutes_per_day: int, project_breakdown: array<int, mixed>}>
     */
    private function buildWeekBreakdown(Collection $sessions, CarbonImmutable $startOfMonth, CarbonImmutable $endOfMonth): array
    {
        $weeks = [];
        $weekIndex = 1;
        $current = $startOfMonth;
        $today = CarbonImmutable::today();

        while ($current->lte($endOfMonth)) {
            if ($current->gt($today)) {
                break;
            }

            $weekEnd = $current->endOfWeek();
            $clampedEnd = $weekEnd->gt($endOfMonth) ? $endOfMonth : $weekEnd;

            $weekSessions = $sessions->filter(
                fn (Session $s) => $s->date !== null
                    && $s->date->gte($current)
                    && $s->date->lte($clampedEnd)
            );

            $total = (int) $weekSessions->sum('rounded_minutes');
            $workedDays = $weekSessions->groupBy(fn (Session $s) => $s->date?->toDateString())->count();

            if ($total > 0) {
                $weeks[] = [
                    'label' => 'Week '.$weekIndex,
                    'start_date' => $current,
                    'end_date' => $clampedEnd,
                    'total_minutes' => $total,
                    'worked_days' => $workedDays,
                    'avg_minutes_per_day' => $workedDays > 0 ? (int) round($total / $workedDays) : 0,
                    'project_breakdown' => $this->buildProjectBreakdown($weekSessions->values(), $total),
                ];
            }

            $weekIndex++;
            $current = $clampedEnd->addDay();
        }

        return $weeks;
    }

    /**
     * @param  Collection<int, Session>  $sessions
     * @return array<int, array{id: string|null, name: string|null, color: string|null, minutes: int, percentage: float}>
     */
    private function buildProjectBreakdown(Collection $sessions, int $totalMinutes): array
    {
        if ($totalMinutes === 0) {
            return [];
        }

        return $sessions
            ->groupBy('project_id')
            ->map(function (Collection $group) use ($totalMinutes): array {
                /** @var Session $firstSession */
                $firstSession = $group->first();

                $minutes = (int) $group->sum('rounded_minutes');

                return [
                    'id' => $firstSession->project_id,
                    'name' => $firstSession->project?->name,
                    'color' => $firstSession->project?->color,
                    'minutes' => $minutes,
                    'percentage' => round($minutes / $totalMinutes * 100, 1),
                ];
            })
            ->sortByDesc('minutes')
            ->values()
            ->all();
    }
}
