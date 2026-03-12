<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Project\AttachRepository;
use App\Actions\Project\DetachRepository;
use App\Http\Requests\Project\StoreProjectRepositoryRequest;
use App\Models\Project;
use App\Models\ProjectRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProjectRepositoryController extends Controller
{
    public function store(StoreProjectRepositoryRequest $request, Project $project, AttachRepository $action): RedirectResponse
    {
        $action->handle($project, $request->validated('local_path'), $request->validated('name'));

        Inertia::flash('success', 'Repository attached successfully.');

        return back();
    }

    public function destroy(Project $project, ProjectRepository $repository, DetachRepository $action): RedirectResponse
    {
        $action->handle($repository);

        Inertia::flash('success', 'Repository removed successfully.');

        return back();
    }
}
