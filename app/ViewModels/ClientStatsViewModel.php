<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Client;
use App\Models\Project;
use App\Models\Session;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;

class ClientStatsViewModel implements ProvidesInertiaProperty
{
    public function __construct(private readonly Client $client) {}

    /**
     * @return array{
     *   projects_count: int,
     *   active_projects_count: int,
     *   worked_days: int,
     *   total_minutes: int,
     *   avg_minutes_per_day: int,
     *   first_date: string|null,
     *   last_date: string|null,
     *   project_breakdown: array<int, array{id: string, name: string, color: string, minutes: int, days: int, percentage: int}>
     * }
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        $projects = Project::query()
            ->where('client_id', $this->client->id)
            ->get(['id', 'name', 'color', 'is_active']);

        $projectIds = $projects->pluck('id');

        $stats = Session::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('ended_at')
            ->selectRaw('COUNT(DISTINCT date) as worked_days, SUM(rounded_minutes) as total_minutes, MIN(date) as first_date, MAX(date) as last_date')
            ->first()
            ?->toArray() ?? [];

        $workedDays = (int) ($stats['worked_days'] ?? 0);
        $totalMinutes = (int) ($stats['total_minutes'] ?? 0);

        $perProject = Session::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('ended_at')
            ->selectRaw('project_id, SUM(rounded_minutes) as minutes, COUNT(DISTINCT date) as days')
            ->groupBy('project_id')
            ->get()
            ->mapWithKeys(fn (Session $row) => [
                $row->project_id => $row->toArray(),
            ]);

        $breakdown = $projects
            ->map(function (Project $project) use ($perProject, $totalMinutes): array {
                $row = $perProject->get($project->id) ?? [];
                $minutes = (int) ($row['minutes'] ?? 0);

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'color' => $project->color,
                    'minutes' => $minutes,
                    'days' => (int) ($row['days'] ?? 0),
                    'percentage' => $totalMinutes > 0 ? (int) round($minutes / $totalMinutes * 100) : 0,
                ];
            })
            ->filter(fn (array $p) => $p['minutes'] > 0)
            ->sortByDesc('minutes')
            ->values()
            ->all();

        return [
            'projects_count' => $projects->count(),
            'active_projects_count' => $projects->where('is_active', true)->count(),
            'worked_days' => $workedDays,
            'total_minutes' => $totalMinutes,
            'avg_minutes_per_day' => $workedDays > 0 ? (int) round($totalMinutes / $workedDays) : 0,
            'first_date' => $stats['first_date'] ?? null,
            'last_date' => $stats['last_date'] ?? null,
            'project_breakdown' => $breakdown,
        ];
    }
}
