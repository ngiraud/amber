<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Models\Project;

class ToggleProjectStatus extends Action
{
    public function handle(Project $project): Project
    {
        $project->update(['is_active' => ! $project->is_active]);

        return $project;
    }
}
