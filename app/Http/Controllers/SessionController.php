<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Http\Requests\Session\StopSessionRequest;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SessionResource;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('session/Index', [
            'sessions' => SessionResource::collection(
                Session::query()->with('project.client')->latest('started_at')->paginate()
            ),
            'projects' => ProjectResource::collection(
                Project::active()->with('client')->get()
            ),
        ]);
    }

    public function show(Session $session): Response
    {
        $session->load('project.client');

        return Inertia::render('session/Show', [
            'session' => SessionResource::make($session),
        ]);
    }

    public function store(StoreSessionRequest $request, StartSession $action): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $action->handle($project, $request->validated('notes'));

        return redirect()->route('sessions.index');
    }

    public function stop(StopSessionRequest $request, Session $session, StopSession $action): RedirectResponse
    {
        $action->handle($session);

        return redirect()->route('sessions.index');
    }
}
