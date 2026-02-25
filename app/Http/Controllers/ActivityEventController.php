<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ActivityEventResource;
use App\Models\ActivityEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityEventController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('activity/Index', [
            'events' => Inertia::scroll(
                ActivityEventResource::collection(
                    ActivityEvent::query()
                        ->with(['project', 'projectRepository'])
                        ->latest('occurred_at')
                        ->cursorPaginate(50)
                )
            ),
            'hasNewEvents' => $request->filled('since_id') && ActivityEvent::query()->where('id', '>', $request->string('since_id'))->exists(),
        ]);
    }
}
