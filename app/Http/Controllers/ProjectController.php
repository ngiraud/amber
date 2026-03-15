<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\DeleteProject;
use App\Actions\Project\UpdateProject;
use App\Data\ProjectData;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\ViewModels\EventsViewModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('project/Index', [
            'projects' => fn () => ProjectResource::collection(
                Project::query()->with('client')->withCount('repositories')->orderBy('name')->paginate()
            ),
            'clients' => fn () => ClientResource::collection(Client::query()->orderBy('name')->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, CreateProject $action): RedirectResponse
    {
        $project = $action->handle(ProjectData::fromArray($request->validated()));

        return redirect()->route('projects.show', $project);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, EventsViewModel $eventsViewModel): Response
    {
        return Inertia::render('project/Show', [
            'client' => fn () => ClientResource::make($project->client),
            'project' => fn () => ProjectResource::make($project->load('repositories')),
            'clients' => fn () => ClientResource::collection(Client::query()->orderBy('name')->get()),
            $eventsViewModel,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $action): RedirectResponse
    {
        $action->handle(project: $project, data: ProjectData::fromArray($request->validated()));

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, DeleteProject $action): RedirectResponse
    {
        $clientId = $project->client_id;

        $action->handle($project);

        return redirect()->route('clients.show', $clientId);
    }
}
