<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Timeline\ShowTimelineDayRequest;
use App\Http\Requests\Timeline\ViewTimelineRequest;
use App\Http\Resources\SessionResource;
use App\Models\Project;
use App\Models\Session;
use App\ViewModels\TimelineIndexViewModel;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function index(ViewTimelineRequest $request, TimelineIndexViewModel $viewModel): Response
    {
        return Inertia::render('timeline/Index', $viewModel);
    }

    public function show(ShowTimelineDayRequest $request): Response
    {
        $day = $request->getDate();

        $sessions = Session::query()
            ->with('project.client')
            ->where('date', $day)
            ->orderBy('started_at')
            ->get();

        return Inertia::render('timeline/Show', [
            'date' => $day->toDateString(),
            'previous_date' => $day->subDay()->toDateString(),
            'next_date' => $day->addDay()->toDateString(),
            'projects' => fn () => Project::active()->with('client')->get(),
            'sessions' => SessionResource::collection($sessions),
            'total_minutes' => $sessions->sum('rounded_minutes'),
        ]);
    }
}
