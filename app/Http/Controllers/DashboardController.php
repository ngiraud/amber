<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\SessionResource;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = CarbonImmutable::today();

        $todaySessions = Session::query()
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

        return Inertia::render('Dashboard', [
            'date' => $today->toDateString(),
            'sessions' => SessionResource::collection($todaySessions),
            'total_minutes' => $todaySessions->sum('rounded_minutes'),
            'week_minutes' => $weekMinutes,
            'month_minutes' => $monthMinutes,
            'projects' => ProjectResource::collection(
                Project::active()->with('client')->get()
            ),
        ]);
    }
}
