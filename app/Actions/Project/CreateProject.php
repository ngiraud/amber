<?php

declare(strict_types=1);

namespace App\Actions\Project;

use App\Actions\Action;
use App\Data\ProjectData;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class CreateProject extends Action
{
    public function handle(Client $client, ProjectData $data): Project
    {
        return DB::transaction(function () use ($client, $data) {
            return $client->projects()->create($data->toArray());
        });
    }
}
