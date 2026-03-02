<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DeleteProject extends Action
{
    public function handle(Project $project): void
    {
        DB::transaction(fn () => $project->delete());

        defer(function () use ($project) {
            $project->repositories()->delete();
            $project->sessions()->delete();
            $project->activityEvents()->delete();
        });
    }
}
