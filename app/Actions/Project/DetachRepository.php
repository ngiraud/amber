<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Models\ProjectRepository;
use Illuminate\Support\Facades\DB;

class DetachRepository extends Action
{
    public function handle(ProjectRepository $repository): void
    {
        DB::transaction(fn () => $repository->delete());

        defer(fn () => $repository->activityEvents()->delete());
    }
}
