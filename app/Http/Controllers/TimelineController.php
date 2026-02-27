<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Timeline\ShowTimelineDayRequest;
use App\Http\Requests\Timeline\ViewTimelineRequest;
use App\Http\Resources\SessionResource;
use App\Models\Project;
use App\Models\Session;
use App\ViewModels\EventsViewModel;
use App\ViewModels\TimelineIndexViewModel;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function index(ViewTimelineRequest $request, TimelineIndexViewModel $viewModel): Response
    {
        return Inertia::render('timeline/Index', $viewModel);
    }

    public function show(ShowTimelineDayRequest $request, string $date, ?Session $session, EventsViewModel $eventsViewModel): Response
    {
        $day = $request->getDate();

        return Inertia::render('timeline/Show', [
            'date' => $day->toDateString(),
            'previous_date' => $day->subDay()->toDateString(),
            'next_date' => $day->addDay()->toDateString(),
            'projects' => fn () => Project::active()->with('client')->get(),
            'sessions' => fn () => SessionResource::collection(
                Session::query()
                    ->with('project.client')
                    ->where('date', $day)
                    ->orderBy('started_at')
                    ->get()
            ),
            'total_minutes' => fn () => Session::where('date', $day)->sum('rounded_minutes'),
            'selectedSession' => $session->id ? SessionResource::make($session) : null,
            $eventsViewModel,
        ]);
    }
}
