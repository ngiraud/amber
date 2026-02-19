<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DeleteProject extends Action
{
    public function handle(Project $project): Client
    {
        return DB::transaction(function () use ($project) {
            return $project->delete();
        });
    }
}
