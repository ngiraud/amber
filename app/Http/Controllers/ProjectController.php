<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Project\CreateProject;
use App\Actions\Project\DeleteProject;
use App\Actions\Project\UpdateProject;
use App\Data\ProjectData;
use App\Http\Requests\Project\ListProjectRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListProjectRequest $request): Response
    {
        $filteredClients = $request->validated('client_id');
        $hasMoreClients = count($filteredClients) > 1;

        return Inertia::render('project/Index', [
            'projects' => ProjectResource::collection(
                Project::query()
                    ->with('client')
                    ->when(
                        ! empty($filteredClients) && $hasMoreClients,
                        fn (Builder $query) => $query->whereIn('client_id', $filteredClients)
                    )
                    ->when(
                        ! empty($filteredClients) && ! $hasMoreClients,
                        fn (Builder $query) => $query->where('client_id', $filteredClients)
                    )
                    ->cursorPaginate()
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Client $client): Response
    {
        return Inertia::render('project/Create', [
            'client' => ClientResource::make($client),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, Client $client, CreateProject $action): RedirectResponse
    {
        $project = $action->handle($client, ProjectData::fromArray($request->validated()));

        return redirect()->route('projects.show', [$client, $project]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client, Project $project): Response
    {
        return Inertia::render('project/Show', [
            'client' => ClientResource::make($client),
            'project' => ProjectResource::make($project),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client, Project $project): Response
    {
        return Inertia::render('project/Show', [
            'client' => ClientResource::make($client),
            'project' => ProjectResource::make($project),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Client $client, Project $project, UpdateProject $action): RedirectResponse
    {
        $action->handle($project, ProjectData::fromArray($request->validated()));

        return redirect()->route('projects.show', [$client, $project]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client, Project $project, DeleteProject $action): RedirectResponse
    {
        $action->handle($project);

        return redirect()->route('projects.index');
    }
}
