<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Data\ProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class UpdateProject extends Action
{
    public function handle(Project $project, ProjectData $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->update($data->toArray());

            return $project;
        });
    }
}
