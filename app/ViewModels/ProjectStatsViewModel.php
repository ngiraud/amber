<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Project;
use App\Models\Session;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;

class ProjectStatsViewModel implements ProvidesInertiaProperty
{
    public function __construct(private readonly Project $project) {}

    /**
     * @return array{worked_days: int, total_minutes: int, total_real_minutes: int, total_days: float, total_real_days: float, avg_minutes_per_day: int, first_date: string|null, last_date: string|null}
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        $stats = Session::query()
            ->where('project_id', $this->project->id)
            ->whereNotNull('ended_at')
            ->selectRaw('COUNT(DISTINCT date) as worked_days, SUM(rounded_minutes) as total_minutes, SUM(duration_minutes) as total_real_minutes, MIN(date) as first_date, MAX(date) as last_date')
            ->first()
            ?->toArray() ?? [];

        $workedDays = (int) ($stats['worked_days'] ?? 0);
        $totalMinutes = (int) ($stats['total_minutes'] ?? 0);
        $totalRealMinutes = (int) ($stats['total_real_minutes'] ?? 0);
        $dailyReferenceMinutes = $this->project->daily_reference_hours * 60;

        return [
            'worked_days' => $workedDays,
            'total_minutes' => $totalMinutes,
            'total_real_minutes' => $totalRealMinutes,
            'total_days' => round($totalMinutes / $dailyReferenceMinutes, 2),
            'total_real_days' => round($totalRealMinutes / $dailyReferenceMinutes, 2),
            'avg_minutes_per_day' => $workedDays > 0 ? (int) round($totalMinutes / $workedDays) : 0,
            'first_date' => $stats['first_date'] ?? null,
            'last_date' => $stats['last_date'] ?? null,
        ];
    }
}
