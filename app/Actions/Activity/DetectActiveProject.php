<?php

declare(strict_types=1);

namespace App\Actions\Activity;

use App\Actions\Action;
use App\Models\ProjectRepository;

// @TODO: Name of the action | Utility of the action
class DetectActiveProject extends Action
{
    /**
     * Match a file path to a project via project_repositories.local_path.
     *
     * @return array{project_id: ?string, project_repository_id: ?string}
     */
    public function handle(?string $filePath): array
    {
        if ($filePath === null) {
            return ['project_id' => null, 'project_repository_id' => null];
        }

        // @TODO: starts with replaced by LIKE?
        $match = ProjectRepository::query()
            ->forActiveProjects()
            ->get()
            ->filter(fn (ProjectRepository $repo) => str_starts_with($filePath, $repo->local_path))
            ->sortByDesc(fn (ProjectRepository $repo) => mb_strlen($repo->local_path))
            ->first();

        if ($match === null) {
            return ['project_id' => null, 'project_repository_id' => null];
        }

        return [
            'project_id' => $match->project_id,
            'project_repository_id' => $match->id,
        ];
    }
}
