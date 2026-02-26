<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Session\CreateSession;
use App\Actions\Session\DeleteSession;
use App\Actions\Session\UpdateSession;
use App\Data\SessionData;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Http\Requests\Session\UpdateSessionRequest;
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

    public function store(StoreSessionRequest $request, CreateSession $action): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $action->handle($project, SessionData::fromArray($request->validated()));

        return redirect()->back();
    }

    public function update(UpdateSessionRequest $request, Session $session, UpdateSession $action): RedirectResponse
    {
        $action->handle($session, SessionData::fromArray($request->validated()));

        return redirect()->back();
    }

    public function destroy(Session $session, DeleteSession $action): RedirectResponse
    {
        $action->handle($session);

        return redirect()->back();
    }
}
