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
use Illuminate\Http\Request;
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
            'projects' => ProjectResource::collection(
                Project::query()->with('client')->withCount('repositories')->latest('id')->paginate()
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $client = Client::findOrFail($request->query('client_id'));

        return Inertia::render('project/Form', [
            'client' => ClientResource::make($client),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, CreateProject $action): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));
        $project = $action->handle($client, ProjectData::fromArray($request->validated()));

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
            $eventsViewModel,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('project/Form', [
            'client' => ClientResource::make($project->client),
            'project' => ProjectResource::make($project),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $action): RedirectResponse
    {
        $action->handle($project, ProjectData::fromArray($request->validated()));

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, DeleteProject $action): RedirectResponse
    {
        $client_id = $project->client_id;

        $action->handle($project);

        return redirect()->route('clients.show', $client_id);
    }
}
