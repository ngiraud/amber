<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Data\ProjectData;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class CreateProject extends Action
{
    public function __construct(protected AttachRepository $attachRepository) {}

    public function handle(ProjectData $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create($data->toArray());

            foreach ($data->repositories as $repo) {
                $this->attachRepository->handle($project, $repo);
            }

            return $project;
        });
    }
}
