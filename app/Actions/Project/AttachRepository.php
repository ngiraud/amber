<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Models\Project;
use App\Models\ProjectRepository;
use Illuminate\Support\Facades\DB;

class AttachRepository extends Action
{
    public function handle(Project $project, string $localPath, string $name): ProjectRepository
    {
        return DB::transaction(function () use ($project, $localPath, $name) {
            return $project->repositories()->create([
                'local_path' => $localPath,
                'name' => $name,
            ]);
        });
    }
}
