<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ViewModels\EventsViewModel;
use Inertia\Inertia;
use Inertia\Response;

class ActivityEventController extends Controller
{
    public function __invoke(EventsViewModel $eventsViewModel): Response
    {
        return Inertia::render('activity/Index', $eventsViewModel);
    }
}
