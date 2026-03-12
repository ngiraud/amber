<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Project\ToggleProjectStatus;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ToggleProjectStatusController extends Controller
{
    public function __invoke(Project $project, ToggleProjectStatus $action): RedirectResponse
    {
        $updated = $action->handle($project);

        Inertia::flash('success', $updated->is_active ? 'Project restored successfully.' : 'Project archived successfully.');

        return back();
    }
}
