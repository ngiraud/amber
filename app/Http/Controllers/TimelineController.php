<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Timeline\ShowTimelineDayRequest;
use App\Http\Requests\Timeline\ViewTimelineRequest;
use App\ViewModels\TimelineIndexViewModel;
use App\ViewModels\TimelineShowViewModel;
use Inertia\Inertia;
use Inertia\Response;

class TimelineController extends Controller
{
    public function index(ViewTimelineRequest $request, TimelineIndexViewModel $viewModel): Response
    {
        return Inertia::render('timeline/Index', $viewModel);
    }

    public function show(ShowTimelineDayRequest $request, TimelineShowViewModel $viewModel): Response
    {
        return Inertia::render('timeline/Show', $viewModel);
    }
}
