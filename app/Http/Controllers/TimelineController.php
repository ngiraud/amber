<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\SessionResource;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $startOfMonth = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->endOfMonth();

        $sessions = Session::query()
            ->with('project')
            ->whereNotNull('ended_at')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $days = collect();
        $current = $startOfMonth;

        while ($current->lte($endOfMonth)) {
            $daySessions = $sessions->filter(
                fn (Session $s) => $s->date?->isSameDay($current)
            )->values();

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

        return Inertia::render('timeline/Index', [
            'year' => $year,
            'month' => $month,
            'days' => $days,
        ]);
    }

    public function show(string $date): Response
    {
        $day = CarbonImmutable::parse($date);

        $sessions = Session::query()
            ->with('project.client')
            ->whereDate('date', $day)
            ->orderBy('started_at')
            ->get();

        $projects = Project::active()->with('client')->get();

        return Inertia::render('timeline/Show', [
            'date' => $day->toDateString(),
            'sessions' => SessionResource::collection($sessions),
            'total_minutes' => $sessions->sum('rounded_minutes'),
            'projects' => ProjectResource::collection($projects),
        ]);
    }
}
