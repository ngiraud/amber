<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Actions\Action;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRepository;
use App\Models\Session;
use Illuminate\Support\Facades\DB;

class DeleteClient extends Action
{
    public function handle(Client $client): void
    {
        $projectIds = $client->projects()->pluck('id');

        DB::transaction(fn () => $client->delete());

        defer(function () use ($projectIds) {
            ActivityEvent::query()->whereIn('project_id', $projectIds)->delete();
            Session::query()->whereIn('project_id', $projectIds)->delete();
            ProjectRepository::query()->whereIn('project_id', $projectIds)->delete();
            Project::query()->whereIn('id', $projectIds)->delete();
        });
    }
}
