<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Data\ProjectRepositoryData;
use App\Models\Project;
use App\Models\ProjectRepository;
use Illuminate\Support\Facades\DB;

class AttachRepository extends Action
{
    public function handle(Project $project, ProjectRepositoryData $data): ProjectRepository
    {
        return DB::transaction(function () use ($project, $data) {
            return $project->repositories()->create($data->toArray());
        });
    }
}
